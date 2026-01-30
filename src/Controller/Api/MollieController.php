<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\MolliePlugin\Client\MollieApiClient;
use Sylius\MolliePlugin\Converter\IntToStringConverterInterface;
use Sylius\MolliePlugin\Entity\GatewayConfigInterface;
use Sylius\MolliePlugin\Entity\MollieGatewayConfigInterface;
use Sylius\MolliePlugin\Form\Type\MollieGatewayConfigurationType;
use Sylius\MolliePlugin\Repository\MollieGatewayConfigRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api/v2/shop')]
final class MollieController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly MollieGatewayConfigRepositoryInterface $mollieGatewayConfigRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MollieApiClient $mollieApiClient,
        private readonly IntToStringConverterInterface $intToStringConverter,
        private readonly UrlGeneratorInterface $router,
        private readonly StateMachineInterface $stateMachine,
    ) {
    }

    #[Route('/orders/{tokenValue}/mollie-methods', name: 'api_shop_mollie_methods', methods: ['GET'])]
    public function getMethods(string $tokenValue): JsonResponse
    {
        $order = $this->getOrder($tokenValue);
        $payment = $order->getLastPayment();

        if (!$payment) {
            return new JsonResponse(['error' => 'No payment found'], Response::HTTP_BAD_REQUEST);
        }

        $paymentMethod = $payment->getMethod();
        if (!$paymentMethod) {
            return new JsonResponse(['error' => 'No payment method found'], Response::HTTP_BAD_REQUEST);
        }

        $gatewayConfig = $paymentMethod->getGatewayConfig();
        if (!$gatewayConfig instanceof GatewayConfigInterface) {
            return new JsonResponse(['error' => 'No gateway config found'], Response::HTTP_BAD_REQUEST);
        }

        $mollieConfigs = $this->mollieGatewayConfigRepository->findAllEnabledByGateway($gatewayConfig);

        $result = [];
        foreach ($mollieConfigs as $config) {
            if (isset($config[0]) && $config[0] instanceof MollieGatewayConfigInterface) {
                $mollieConfig = $config[0];
                $image = $mollieConfig->getImage();
                $result[] = [
                    'methodId' => $mollieConfig->getMethodId(),
                    'name' => $mollieConfig->getName(),
                    'image' => $image['svg'] ?? $image['size1x'] ?? null,
                ];
            }
        }

        return new JsonResponse($result);
    }

    #[Route('/orders/{tokenValue}/mollie-methods', name: 'api_shop_mollie_select_method', methods: ['POST'])]
    public function selectMethod(string $tokenValue, Request $request): JsonResponse
    {
        $order = $this->getOrder($tokenValue);
        $data = json_decode($request->getContent(), true);
        $methodId = $data['methodId'] ?? null;

        if (!$methodId) {
            return new JsonResponse(['error' => 'methodId is required'], Response::HTTP_BAD_REQUEST);
        }

        $payment = $order->getLastPayment();
        if (!$payment) {
            return new JsonResponse(['error' => 'No payment found'], Response::HTTP_BAD_REQUEST);
        }

        $apiKey = $this->getApiKey($payment);
        if (!$apiKey) {
            return new JsonResponse(['error' => 'Mollie API key not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->mollieApiClient->setApiKey($apiKey);

        $webhookUrl = $this->router->generate(
            'sylius_mollie_shop_payment_webhook',
            ['_locale' => $order->getLocaleCode() ?? 'en_US'],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $redirectUrl = $data['redirectUrl'] ?? $this->router->generate(
            'api_shop_mollie_status',
            ['tokenValue' => $tokenValue],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $molliePayment = $this->mollieApiClient->payments->create([
            'method' => $methodId,
            'amount' => [
                'currency' => $payment->getCurrencyCode(),
                'value' => $this->intToStringConverter->convertIntToString($payment->getAmount()),
            ],
            'description' => $order->getNumber(),
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $webhookUrl,
            'metadata' => [
                'order_id' => $order->getId(),
                'customer_id' => $order->getCustomer()?->getId(),
                'molliePaymentMethods' => $methodId,
            ],
        ]);

        $payment->setDetails([
            'molliePaymentMethods' => $methodId,
            'payment_mollie_id' => $molliePayment->id,
            'cartToken' => null,
            'saveCardInfo' => '0',
            'useSavedCards' => '0',
            'webhookUrl' => $webhookUrl,
            'backurl' => $redirectUrl,
        ]);

        $this->entityManager->flush();

        $checkoutUrl = $molliePayment->_links->checkout->href ?? null;
        if (!$checkoutUrl) {
            return new JsonResponse(['error' => 'No checkout URL returned from Mollie'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'success' => true,
            'methodId' => $methodId,
            'checkoutUrl' => $checkoutUrl,
            'paymentId' => $molliePayment->id,
        ]);
    }

    #[Route('/orders/{tokenValue}/mollie-status', name: 'api_shop_mollie_status', methods: ['GET'])]
    public function getStatus(string $tokenValue): JsonResponse
    {
        $order = $this->getOrder($tokenValue);
        $payment = $order->getLastPayment();

        if (!$payment) {
            return new JsonResponse(['error' => 'No payment found'], Response::HTTP_BAD_REQUEST);
        }

        $details = $payment->getDetails();
        $molliePaymentId = $details['payment_mollie_id'] ?? null;

        if (!$molliePaymentId) {
            return new JsonResponse([
                'paymentState' => $payment->getState(),
                'mollieStatus' => null,
                'message' => 'No Mollie payment created yet',
            ]);
        }

        $apiKey = $this->getApiKey($payment);
        if (!$apiKey) {
            return new JsonResponse(['error' => 'Mollie API key not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->mollieApiClient->setApiKey($apiKey);
        $molliePayment = $this->mollieApiClient->payments->get($molliePaymentId);
        $mollieStatus = $molliePayment->status;

        $transition = match ($mollieStatus) {
            'pending', 'open' => 'process',
            'authorized' => 'authorize',
            'paid' => 'complete',
            'canceled' => 'cancel',
            'expired', 'failed' => 'fail',
            default => null,
        };

        if ($transition !== null && $this->stateMachine->can($payment, 'sylius_payment', $transition)) {
            $this->stateMachine->apply($payment, 'sylius_payment', $transition);
            $this->entityManager->flush();
        }

        return new JsonResponse([
            'orderNumber' => $order->getNumber(),
            'orderToken' => $tokenValue,
            'paymentState' => $payment->getState(),
            'mollieStatus' => $mollieStatus,
            'molliePaymentId' => $molliePaymentId,
            'paid' => $molliePayment->isPaid(),
        ]);
    }

    private function getOrder(string $tokenValue): OrderInterface
    {
        $order = $this->orderRepository->findOneByTokenValue($tokenValue);

        if (!$order) {
            throw new NotFoundHttpException(sprintf('Order with token "%s" not found', $tokenValue));
        }

        return $order;
    }

    private function getApiKey(PaymentInterface $payment): ?string
    {
        $paymentMethod = $payment->getMethod();
        if (!$paymentMethod) {
            return null;
        }

        $gatewayConfig = $paymentMethod->getGatewayConfig();
        if (!$gatewayConfig) {
            return null;
        }

        $config = $gatewayConfig->getConfig();

        return ($config['environment'] ?? false)
            ? ($config[MollieGatewayConfigurationType::API_KEY_LIVE] ?? null)
            : ($config[MollieGatewayConfigurationType::API_KEY_TEST] ?? null);
    }
}
