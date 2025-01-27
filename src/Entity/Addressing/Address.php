<?php

declare(strict_types=1);

namespace App\Entity\Addressing;

use Sylius\Plus\Loyalty\Tests\Unit\Infrastructure\Fixture\LoyaltyRuleFixtureTest
use Sylius\Plus\Loyalty\Application\Command\CustomerEmailAwareInterface
use Sylius\Plus\Returns\Infrastructure\Doctrine\QueryItemExtension\OrderShopUserItemExtension
use Sylius\Plus\Inventory\Infrastructure\Form\Extension\ProductVariantTypeExtension
use Sylius\Plus\Inventory\Infrastructure\Validator\CartItemAvailabilityValidator
use Sylius\Plus\PartialShipping\Application\ShipmentTransitions
use Sylius\Plus\ChannelAdmin\Application\Checker\ChannelAwareResourceChannelChecker;
use Sylius\Plus\ChannelAdmin\Application\Checker\ChannelsAwareResourceChannelChecker;
use Sylius\Plus\ChannelAdmin\Application\Checker\OrderAwareResourceChannelChecker;
use Sylius\Plus\ChannelAdmin\Application\Checker\ProductVariantResourceChannelChecker;
use Sylius\Plus\ChannelAdmin\Application\Checker\ResourceChannelChecker;
use Sylius\Plus\ChannelAdmin\Application\Checker\ResourceChannelEnabilibityChecker;
use Sylius\Plus\ChannelAdmin\Application\Checker\ResourceChannelEnabilibityCheckerInterface;
use Sylius\Plus\ChannelAdmin\Application\EventListener\AdminUserChannelEnableListener;
use Sylius\Plus\ChannelAdmin\Application\Factory\ChannelRestrictingNewResourceFactory;
use Sylius\Plus\ChannelAdmin\Application\Provider\AdminChannelProvider;
use Sylius\Plus\ChannelAdmin\Application\Provider\AdminChannelProviderInterface;
use Sylius\Plus\ChannelAdmin\Application\Provider\AvailableChannelsForAdminProvider;
use Sylius\Plus\ChannelAdmin\Application\Provider\AvailableChannelsForAdminProviderInterface;
use Sylius\Plus\ChannelAdmin\Application\Provider\ResourcesCollectionProvider;
use Sylius\Plus\ChannelAdmin\Application\Provider\SingleResourceProvider;
use Sylius\Plus\ChannelAdmin\Domain\Model\AdminChannelAwareTrait;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\Filter\AdminUserChannelFilter;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\ChannelRestrictingProductListQueryBuilder;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\ChannelRestrictingProductListQueryBuilderInterface;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\CreateOrderListQueryBuilderTrait;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\CreatePaymentListQueryBuilderTrait;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\CreateShipmentListQueryBuilderTrait;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\CreditMemoListQueryBuilder;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\CreditMemoListQueryBuilderInterface;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\CustomerListQueryBuilder;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\CustomerListQueryBuilderInterface;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\FindLatestCustomersQueryTrait;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\FindProductsByChannelAndPhraseQuery;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\FindProductsByChannelAndPhraseQueryInterface;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\InvoiceListQueryBuilder;
use Sylius\Plus\ChannelAdmin\Infrastructure\Doctrine\ORM\InvoiceListQueryBuilderInterface;
use Sylius\Plus\ChannelAdmin\Infrastructure\Form\Extension\ChannelRestrictingProductTypeExtension;
use Sylius\Plus\ChannelAdmin\Infrastructure\Form\Extension\ChannelRestrictingProductVariantGenerationTypeExtension;
use Sylius\Plus\ChannelAdmin\Infrastructure\Form\Extension\ChannelRestrictingProductVariantTypeExtension;
use Sylius\Plus\ChannelAdmin\Infrastructure\Twig\AdminChannelExtension;
use Sylius\Plus\ChannelAdmin\Tests\Unit\Domain\Model\AdminChannelAwareTraitTest;
use Sylius\Plus\Controller\DashboardController;
use Sylius\Plus\Entity\LastLoginIpAwareInterface;
use Sylius\Plus\Entity\LastLoginIpAwareTrait;
use Sylius\Plus\Factory\VariantsQuantityMapFactory;
use Sylius\Plus\Factory\VariantsQuantityMapFactoryInterface;
use Sylius\Plus\Inventory\Application\Assigner\ShipmentInventorySourceAssigner;
use Sylius\Plus\Inventory\Application\Assigner\ShipmentInventorySourceAssignerInterface;
use Sylius\Plus\Inventory\Application\Checker\AvailabilityChecker;
use Sylius\Plus\Inventory\Application\Checker\AvailabilityCheckerInterface;
use Sylius\Plus\Inventory\Application\Checker\IsStockSufficientChecker;
use Sylius\Plus\Inventory\Application\Checker\IsStockSufficientCheckerInterface;
use Sylius\Plus\Inventory\Application\Checker\VariantAvailabilityChecker;
use Sylius\Plus\Inventory\Application\Checker\VariantAvailabilityCheckerInterface;
use Sylius\Plus\Inventory\Application\Checker\VariantQuantityMapAvailabilityChecker;
use Sylius\Plus\Inventory\Application\Checker\VariantQuantityMapAvailabilityCheckerInterface;
use Sylius\Plus\Inventory\Application\Command\ModifyInventorySourceStock;
use Sylius\Plus\Inventory\Application\CommandHandler\ModifyInventorySourceStockHandler;
use Sylius\Plus\Inventory\Application\Controller\OrderItemController;
use Sylius\Plus\Inventory\Application\DataPersister\InventorySourceDataPersister;
use Sylius\Plus\Inventory\Application\DataPersister\InventorySourceStockDataPersister;
use Sylius\Plus\Inventory\Application\DataPersister\ProductVariantDataPersister;
use Sylius\Plus\Inventory\Application\EventListener\ChannelCreateListener;
use Sylius\Plus\Inventory\Application\EventListener\InventorySourceDeletionListener;
use Sylius\Plus\Inventory\Application\Factory\InventorySourceFactory;
use Sylius\Plus\Inventory\Application\Factory\InventorySourceFactoryInterface;
use Sylius\Plus\Inventory\Application\Filter\EnabledChannelInventorySourcesFilter;
use Sylius\Plus\Inventory\Application\Filter\InventorySourcesFilterInterface;
use Sylius\Plus\Inventory\Application\Filter\PriorityInventorySourcesFilter;
use Sylius\Plus\Inventory\Application\Filter\SufficientInventorySourcesFilter;
use Sylius\Plus\Inventory\Application\Operator\CancelOrderInventoryOperator;
use Sylius\Plus\Inventory\Application\Operator\CancelOrderInventoryOperatorInterface;
use Sylius\Plus\Inventory\Application\Operator\ChangeInventorySourceOperator;
use Sylius\Plus\Inventory\Application\Operator\ChangeInventorySourceOperatorInterface;
use Sylius\Plus\Inventory\Application\Operator\HoldOrderInventoryOperator;
use Sylius\Plus\Inventory\Application\Operator\HoldOrderInventoryOperatorInterface;
use Sylius\Plus\Inventory\Application\Operator\InventoryOperator;
use Sylius\Plus\Inventory\Application\Operator\InventoryOperatorInterface;
use Sylius\Plus\Inventory\Application\Operator\ShipmentInventoryOperator;
use Sylius\Plus\Inventory\Application\Operator\ShipmentInventoryOperatorInterface;
use Sylius\Plus\Inventory\Application\Operator\ShipShipmentInventoryOperator;
use Sylius\Plus\Inventory\Application\Operator\ShipShipmentInventoryOperatorInterface;
use Sylius\Plus\Inventory\Application\Provider\AvailableInventorySourcesProvider;
use Sylius\Plus\Inventory\Application\Provider\AvailableInventorySourcesProviderInterface;
use Sylius\Plus\Inventory\Application\Provider\InsufficientItemFromOrderItemsProvider;
use Sylius\Plus\Inventory\Application\Provider\InsufficientItemFromOrderItemsProviderInterface;
use Sylius\Plus\Inventory\Application\Resolver\InventorySourceResolver;
use Sylius\Plus\Inventory\Application\Resolver\InventorySourceResolverInterface;
use Sylius\Plus\Inventory\Application\Updater\InventorySourceStockUpdater;
use Sylius\Plus\Inventory\Application\Updater\InventorySourceStockUpdaterInterface;
use Sylius\Plus\Inventory\DependencyInjection\Compiler\InventoryPass;
use Sylius\Plus\Inventory\Domain\Exception\InventorySourceStockInUseException;
use Sylius\Plus\Inventory\Domain\Exception\InventorySourceStockNotFoundException;
use Sylius\Plus\Inventory\Domain\Exception\UnresolvedInventorySource;
use Sylius\Plus\Inventory\Domain\Exception\VariantQuantityAlreadySpecified;
use Sylius\Plus\Inventory\Domain\Exception\VariantQuantityNotSpecified;
use Sylius\Plus\Inventory\Domain\Model\InventoryAwareInterface;
use Sylius\Plus\Inventory\Domain\Model\InventorySource;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceAddress;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceAddressInterface;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceAwareInterface;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceAwareTrait;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceInterface;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceStock;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceStockInterface;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceStocksAwareInterface;
use Sylius\Plus\Inventory\Domain\Model\InventorySourceStocksAwareTrait;
use Sylius\Plus\Inventory\Domain\Model\ProductVariantInterface;
use Sylius\Plus\Inventory\Domain\Model\ShipmentInterface;
use Sylius\Plus\Inventory\Domain\Model\VariantsQuantityMap;
use Sylius\Plus\Inventory\Domain\Model\VariantsQuantityMapInterface;
use Sylius\Plus\Inventory\Infrastructure\Doctrine\ORM\FindAllDescendantProductsByTaxonQuery;
use Sylius\Plus\Inventory\Infrastructure\Doctrine\ORM\FindAllDescendantProductsByTaxonQueryInterface;
use Sylius\Plus\Inventory\Infrastructure\Doctrine\ORM\InventorySourceStockRepository;
use Sylius\Plus\Inventory\Infrastructure\Doctrine\ORM\InventorySourceStockRepositoryInterface;
use Sylius\Plus\Inventory\Infrastructure\Fixture\Factory\InventorySourceExampleFactory;
use Sylius\Plus\Inventory\Infrastructure\Fixture\InventorySourceFixture;
use Sylius\Plus\Inventory\Infrastructure\Fixture\InventorySourceStockFixture;
use Sylius\Plus\Inventory\Infrastructure\Form\Type\InventorySourceAddressType;
use Sylius\Plus\Inventory\Infrastructure\Form\Type\InventorySourceChoiceType;
use Sylius\Plus\Inventory\Infrastructure\Form\Type\InventorySourceCollectionType;
use Sylius\Plus\Inventory\Infrastructure\Form\Type\InventorySourceStockOnHandType;
use Sylius\Plus\Inventory\Infrastructure\Form\Type\InventorySourceStockType;
use Sylius\Plus\Inventory\Infrastructure\Form\Type\InventorySourceType;
use Sylius\Plus\Inventory\Infrastructure\Twig\InventoryExtension;
use Sylius\Plus\Inventory\Infrastructure\Ui\ChangeInventorySourceStockOnHandAction;
use Sylius\Plus\Inventory\Infrastructure\Ui\SplitShipmentAction;
use Sylius\Plus\Inventory\Infrastructure\Validator\CartItemAvailability;
use Sylius\Plus\Inventory\Infrastructure\Validator\NoInventorySourceForTrackedItemUnits;
use Sylius\Plus\Inventory\Infrastructure\Validator\NoInventorySourceForTrackedItemUnitsValidator;
use Sylius\Plus\Inventory\Infrastructure\Validator\OrderInStock;
use Sylius\Plus\Inventory\Infrastructure\Validator\OrderInStockValidator;
use Sylius\Plus\Inventory\Infrastructure\Validator\OrderItemClassMetadataLoader;
use Sylius\Plus\Inventory\Infrastructure\Validator\StockOnHandCannotBeLowerThanOnHold;
use Sylius\Plus\Inventory\Infrastructure\Validator\StockOnHandCannotBeLowerThanOnHoldValidator;
use Sylius\Plus\Inventory\Infrastructure\Validator\StockSufficientForInventorySource;
use Sylius\Plus\Inventory\Infrastructure\Validator\StockSufficientForInventorySourceValidator;
use Sylius\Plus\Inventory\Tests\Unit\Application\Updater\InventorySourceStockUpdaterTest;
use Sylius\Plus\Inventory\Tests\Unit\Domain\Model\InventorySourceAwareTraitTest;
use Sylius\Plus\Inventory\Tests\Unit\Domain\Model\InventorySourceStocksAwareTraitTest;
use Sylius\Plus\Inventory\Tests\Unit\Infrastructure\Fixture\InventorySourceTest;
use Sylius\Plus\Loyalty\Application\Assigner\LoyaltyPointsAssigner;
use Sylius\Plus\Loyalty\Application\Assigner\LoyaltyPointsAssignerInterface;
use Sylius\Plus\Loyalty\Application\Calculator\ActionBasedLoyaltyPointsCalculatorInterface;
use Sylius\Plus\Loyalty\Application\Calculator\ChannelsBasedItemsTotalToPointsRatioCalculator;
use Sylius\Plus\Loyalty\Application\Calculator\ChannelsBasedPointsPerProductRatioCalculator;
use Sylius\Plus\Loyalty\Application\Calculator\ConfigurationBasedLoyaltyPointsCalculatorInterface;
use Sylius\Plus\Loyalty\Application\Calculator\DelegatingLoyaltyPointsCalculator;
use Sylius\Plus\Loyalty\Application\Calculator\LoyaltyActionTypes;
use Sylius\Plus\Loyalty\Application\Command\BuyLoyaltyPurchase;
use Sylius\Plus\Loyalty\Application\CommandHandler\BuyLoyaltyPurchaseHandler;
use Sylius\Plus\Loyalty\Application\DataProvider\LoyaltyPointsAccountDataProvider;
use Sylius\Plus\Loyalty\Application\Factory\LoyaltyRuleActionFactory;
use Sylius\Plus\Loyalty\Application\Factory\LoyaltyRuleActionFactoryInterface;
use Sylius\Plus\Loyalty\Application\Generator\LoyaltyPurchasePromotionCouponInstructionGenerator;
use Sylius\Plus\Loyalty\Application\Generator\LoyaltyPurchasePromotionCouponInstructionGeneratorInterface;
use Sylius\Plus\Loyalty\Application\Logger\LoyaltyPointsTransactionLogger;
use Sylius\Plus\Loyalty\Application\Logger\LoyaltyPointsTransactionLoggerInterface;
use Sylius\Plus\Loyalty\Application\Mailer\Emails;
use Sylius\Plus\Loyalty\Application\Modifier\LoyaltyPointsAccountModifier;
use Sylius\Plus\Loyalty\Application\Modifier\LoyaltyPointsAccountModifierInterface;
use Sylius\Plus\Loyalty\Application\Processor\LoyaltyPointsProcessor;
use Sylius\Plus\Loyalty\Application\Provider\LoyaltyPointsAccountProvider;
use Sylius\Plus\Loyalty\Application\Provider\LoyaltyPointsAccountProviderInterface;
use Sylius\Plus\Loyalty\Application\Provider\OrdersLoyaltyPointsProvider;
use Sylius\Plus\Loyalty\Application\Provider\OrdersLoyaltyPointsProviderInterface;
use Sylius\Plus\Loyalty\DependencyInjection\Compiler\LoyaltyRuleActionsPass;
use Sylius\Plus\Loyalty\Domain\Model\AdjustmentType;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyAwareInterface;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyAwareTrait;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyPointsAccount;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyPointsAccountInterface;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyPointsTransaction;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyPointsTransactionInterface;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyPointsTransactionTypes;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyPurchase;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyPurchaseInterface;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRule;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleAction;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleActionInterface;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleConfiguration\ChannelBasedLoyaltyRuleConfigurationInterface;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleConfiguration\ChannelsBasedItemsTotalToPointsRatioConfiguration;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleConfiguration\ChannelsBasedPointsPerProductRatioConfiguration;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleConfiguration\ItemsTotalToPointsRatioConfiguration;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleConfiguration\LoyaltyRuleConfigurationInterface;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleConfiguration\PointsPerProductRatioConfiguration;
use Sylius\Plus\Loyalty\Domain\Model\LoyaltyRuleInterface;
use Sylius\Plus\Loyalty\Infrastructure\DataTransformer\CustomerEmailAwareCommandDataTransformer;
use Sylius\Plus\Loyalty\Infrastructure\DataTransformer\ItemsTotalToPointsRatioConfigurationDataTransformer;
use Sylius\Plus\Loyalty\Infrastructure\DataTransformer\LoyaltyRuleActionDataTransformerInterface;
use Sylius\Plus\Loyalty\Infrastructure\DataTransformer\LoyaltyRuleActionInputDataTransformer;
use Sylius\Plus\Loyalty\Infrastructure\DataTransformer\PointsPerProductRatioDataTransformer;
use Sylius\Plus\Loyalty\Infrastructure\Doctrine\ORM\ChannelRestrictingEnabledLoyaltyPurchaseListQueryBuilder;
use Sylius\Plus\Loyalty\Infrastructure\Doctrine\ORM\ChannelRestrictingEnabledLoyaltyPurchaseListQueryBuilderInterface;
use Sylius\Plus\Loyalty\Infrastructure\Doctrine\ORM\LoyaltyRuleQuery;
use Sylius\Plus\Loyalty\Infrastructure\Doctrine\ORM\LoyaltyRuleQueryInterface;
use Sylius\Plus\Loyalty\Infrastructure\Doctrine\QueryCollectionExtension\EnabledLoyaltyPurchasesByChannelExtension;
use Sylius\Plus\Loyalty\Infrastructure\Fixture\Factory\LoyaltyPurchaseExampleFactory;
use Sylius\Plus\Loyalty\Infrastructure\Fixture\Factory\LoyaltyRuleActionExampleFactory;
use Sylius\Plus\Loyalty\Infrastructure\Fixture\Factory\LoyaltyRuleExampleFactory;
use Sylius\Plus\Loyalty\Infrastructure\Fixture\LoyaltyPurchaseFixture;
use Sylius\Plus\Loyalty\Infrastructure\Fixture\LoyaltyRuleFixture;
use Sylius\Plus\Loyalty\Infrastructure\Form\DataMapper\ChannelBasedRatioConfigurationDataMapper;
use Sylius\Plus\Loyalty\Infrastructure\Form\DataTransformer\ChangeActionToChannelsBasedRatioConfigurationTransformer;
use Sylius\Plus\Loyalty\Infrastructure\Form\DataTransformer\NullableIdentifierToResourceTransformer;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\Action\ChannelsBasedItemsTotalToPointsRatioConfigurationType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\Action\ChannelsBasedPointsPerProductRatioConfigurationType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\Action\ItemsTotalToPointsRatioConfigurationType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\Action\PointsPerProductRatioConfigurationType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\CouponBasedPromotionChoiceType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\LoyaltyPurchaseType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\LoyaltyRuleActionType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\LoyaltyRuleActionTypeChoiceType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\LoyaltyRuleType;
use Sylius\Plus\Loyalty\Infrastructure\Form\Type\ProductPerChannelAutocompleteChoiceType;
use Sylius\Plus\Loyalty\Infrastructure\Menu\LoyaltyAdminMenuListener;
use Sylius\Plus\Loyalty\Infrastructure\Menu\ShopAccountMenuListener;
use Sylius\Plus\Loyalty\Infrastructure\Serializer\LoyaltyRuleDenormalizer;
use Sylius\Plus\Loyalty\Infrastructure\Serializer\OrderNormalizer;
use Sylius\Plus\Loyalty\Infrastructure\Twig\LoyaltyExtension;
use Sylius\Plus\Loyalty\Infrastructure\Ui\Shop\BuyLoyaltyPurchaseAction;
use Sylius\Plus\Loyalty\Infrastructure\Validator\PointsPerProductRatioConfigurationEligibility;
use Sylius\Plus\Loyalty\Infrastructure\Validator\PointsPerProductRatioConfigurationEligibilityValidator;
use Sylius\Plus\Loyalty\Infrastructure\Validator\PromotionEligibility;
use Sylius\Plus\Loyalty\Infrastructure\Validator\PromotionEligibilityValidator;
use Sylius\Plus\Loyalty\Infrastructure\Validator\PurchaseEnabledInChannel;
use Sylius\Plus\Loyalty\Infrastructure\Validator\PurchaseEnabledInChannelValidator;
use Sylius\Plus\Loyalty\Infrastructure\Validator\RegisteredRuleActionType;
use Sylius\Plus\Loyalty\Infrastructure\Validator\RegisteredRuleActionTypeValidator;
use Sylius\Plus\Loyalty\Infrastructure\Validator\SufficientPointsForPurchase;
use Sylius\Plus\Loyalty\Infrastructure\Validator\SufficientPointsForPurchaseValidator;
use Sylius\Plus\Loyalty\Tests\Unit\Domain\Model\LoyaltyAwareTraitTest;
use Sylius\Plus\Loyalty\Tests\Unit\Infrastructure\Fixture\LoyaltyPurchaseFixtureTest;
use Sylius\Plus\Loyalty\Tests\Unit\Infrastructure\Form\Type\Action\ChannelsBasedPointsPerProductRatioConfigurationTypeTest;
use Sylius\Plus\Loyalty\Tests\Unit\Infrastructure\Form\Type\LoyaltyRuleActionTypeTest;
use Sylius\Plus\PartialShipping\Application\Command\SplitAndSendShipment;
use Sylius\Plus\PartialShipping\Application\CommandHandler\SplitAndSendShipmentHandler;
use Sylius\Plus\PartialShipping\Application\Creator\PartialShipmentCreator;
use Sylius\Plus\PartialShipping\Application\Creator\PartialShipmentCreatorInterface;
use Sylius\Plus\PartialShipping\Application\Creator\SplitAndSendShipmentCommandCreator;
use Sylius\Plus\PartialShipping\Application\Creator\SplitAndSendShipmentCommandCreatorInterface;
use Sylius\Plus\PartialShipping\Application\Duplicator\AdjustmentDuplicator;
use Sylius\Plus\PartialShipping\Application\Duplicator\AdjustmentDuplicatorInterface;
use Sylius\Plus\PartialShipping\Application\Factory\ShipmentFactory;
use Sylius\Plus\PartialShipping\Application\Factory\ShipmentFactoryInterface;
use Sylius\Plus\PartialShipping\Application\Purifier\OrderShipmentPurifier;
use Sylius\Plus\PartialShipping\Application\Purifier\OrderShipmentPurifierInterface;
use Sylius\Plus\PartialShipping\Infrastructure\Form\DataTransformer\OrderItemUnitsArrayToOrderItemUnitIdsCollectionTransformer;
use Sylius\Plus\PartialShipping\Infrastructure\Form\Type\PartialShipType;
use Sylius\Plus\PartialShipping\Infrastructure\Form\Type\ShippingUnitsChoiceType;
use Sylius\Plus\PartialShipping\Infrastructure\Modifier\ShipmentUnitModifier;
use Sylius\Plus\PartialShipping\Infrastructure\Modifier\ShipmentUnitModifierInterface;
use Sylius\Plus\PartialShipping\Infrastructure\Splitter\InventorySourceShipmentSplitter;
use Sylius\Plus\PartialShipping\Infrastructure\Splitter\InventorySourceShipmentSplitterInterface;
use Sylius\Plus\PartialShipping\Infrastructure\Splitter\ShipmentSplitter;
use Sylius\Plus\PartialShipping\Infrastructure\Splitter\ShipmentSplitterInterface;
use Sylius\Plus\PartialShipping\Infrastructure\Splitter\ShipmentUnitsSplitter;
use Sylius\Plus\PartialShipping\Infrastructure\Splitter\ShipmentUnitsSplitterInterface;
use Sylius\Plus\PartialShipping\Infrastructure\Validator\SplitShipmentUnitsEligibility;
use Sylius\Plus\PartialShipping\Infrastructure\Validator\SplitShipmentUnitsEligibilityValidator;
use Sylius\Plus\Returns\Application\Calculator\ReturnRateMetricCalculator;
use Sylius\Plus\Returns\Application\Calculator\ReturnRateMetricCalculatorInterface;
use Sylius\Plus\Returns\Application\Calculator\ShippingCalculator\DelegatingCalculator;
use Sylius\Plus\Returns\Application\Checker\OrderItemsAvailabilityChecker;
use Sylius\Plus\Returns\Application\Checker\OrderItemsAvailabilityCheckerInterface;
use Sylius\Plus\Returns\Application\Checker\ReturnRequestAllUnitsReceivedChecker;
use Sylius\Plus\Returns\Application\Checker\ReturnRequestAllUnitsReceivedCheckerInterface;
use Sylius\Plus\Returns\Application\Checker\ReturnRequestAllUnitsReturnedToInventoryChecker;
use Sylius\Plus\Returns\Application\Checker\ReturnRequestAllUnitsReturnedToInventoryCheckerInterface;
use Sylius\Plus\Returns\Application\Checker\ReturnRequestCustomerRelationChecker;
use Sylius\Plus\Returns\Application\Checker\ReturnRequestCustomerRelationCheckerInterface;
use Sylius\Plus\Returns\Application\Checker\ReturnRequestResourceChannelChecker;
use Sylius\Plus\Returns\Application\Command\AcceptReturnRequest;
use Sylius\Plus\Returns\Application\Command\CancelReturnRequest;
use Sylius\Plus\Returns\Application\Command\ChangeReturnRequestResolution;
use Sylius\Plus\Returns\Application\Command\CreateReplacementOrder;
use Sylius\Plus\Returns\Application\Command\MarkReturnRequestAsItemsReturnedToInventory;
use Sylius\Plus\Returns\Application\Command\MarkReturnRequestAsPackageReceived;
use Sylius\Plus\Returns\Application\Command\MarkReturnRequestAsRepairedItemsSent;
use Sylius\Plus\Returns\Application\Command\MarkReturnRequestUnitsAsReceived;
use Sylius\Plus\Returns\Application\Command\Model\ReturnRequestUnitReturnToInventory;
use Sylius\Plus\Returns\Application\Command\Model\ReturnRequestUnitReturnToInventoryInterface;
use Sylius\Plus\Returns\Application\Command\RejectReturnRequest;
use Sylius\Plus\Returns\Application\Command\RequestReturn;
use Sylius\Plus\Returns\Application\Command\ResolveReturnRequest;
use Sylius\Plus\Returns\Application\Command\ReturnUnitsToInventory;
use Sylius\Plus\Returns\Application\Command\SendReturnRequestConfirmation;
use Sylius\Plus\Returns\Application\Command\SendReturnRequestSplitInformation;
use Sylius\Plus\Returns\Application\Command\SplitReturnRequest;
use Sylius\Plus\Returns\Application\CommandHandler\AcceptReturnRequestHandler;
use Sylius\Plus\Returns\Application\CommandHandler\CancelReturnRequestHandler;
use Sylius\Plus\Returns\Application\CommandHandler\ChangeReturnRequestResolutionHandler;
use Sylius\Plus\Returns\Application\CommandHandler\CreateReplacementOrderHandler;
use Sylius\Plus\Returns\Application\CommandHandler\MarkReturnRequestAsItemsReturnedToInventoryHandler;
use Sylius\Plus\Returns\Application\CommandHandler\MarkReturnRequestAsPackageReceivedHandler;
use Sylius\Plus\Returns\Application\CommandHandler\MarkReturnRequestAsRepairedItemsSentHandler;
use Sylius\Plus\Returns\Application\CommandHandler\MarkReturnRequestUnitsAsReceivedHandler;
use Sylius\Plus\Returns\Application\CommandHandler\RejectReturnRequestHandler;
use Sylius\Plus\Returns\Application\CommandHandler\RequestReturnHandler;
use Sylius\Plus\Returns\Application\CommandHandler\ResolveReturnRequestHandler;
use Sylius\Plus\Returns\Application\CommandHandler\ReturnUnitsToInventoryHandler;
use Sylius\Plus\Returns\Application\CommandHandler\SendReturnRequestConfirmationHandler;
use Sylius\Plus\Returns\Application\CommandHandler\SendReturnRequestSplitInformationHandler;
use Sylius\Plus\Returns\Application\CommandHandler\SplitReturnRequestHandler;
use Sylius\Plus\Returns\Application\Controller\AvailableReturnRequestResolutionsController;
use Sylius\Plus\Returns\Application\Controller\ReturnableOrderItemUnitsController;
use Sylius\Plus\Returns\Application\Creator\ChangeReturnRequestResolutionCommandCreator;
use Sylius\Plus\Returns\Application\Creator\ChangeReturnRequestResolutionCommandCreatorInterface;
use Sylius\Plus\Returns\Application\Creator\ReplacementOrderCreator;
use Sylius\Plus\Returns\Application\Creator\ReplacementOrderCreatorInterface;
use Sylius\Plus\Returns\Application\Exception\InsufficientStockWhileCreatingReplacementOrder;
use Sylius\Plus\Returns\Application\Exception\ReturnRequestNotAccessible;
use Sylius\Plus\Returns\Application\Factory\ReplacementOrderFactory;
use Sylius\Plus\Returns\Application\Factory\ReplacementOrderFactoryInterface;
use Sylius\Plus\Returns\Application\Factory\ReplacementOrderItemFactory;
use Sylius\Plus\Returns\Application\Factory\ReplacementOrderItemFactoryInterface;
use Sylius\Plus\Returns\Application\Factory\ReturnRequestFactory;
use Sylius\Plus\Returns\Application\Factory\ReturnRequestFactoryInterface;
use Sylius\Plus\Returns\Application\Factory\ReturnRequestUnitFactory;
use Sylius\Plus\Returns\Application\Factory\ReturnRequestUnitFactoryInterface;
use Sylius\Plus\Returns\Application\Generator\ReturnRequestNumberGenerator;
use Sylius\Plus\Returns\Application\Generator\ReturnRequestPdfFileGenerator;
use Sylius\Plus\Returns\Application\Generator\ReturnRequestPdfFileGeneratorInterface;
use Sylius\Plus\Returns\Application\Generator\SequentialReturnRequestNumberGenerator;
use Sylius\Plus\Returns\Application\Guard\ReturnRequestGuard;
use Sylius\Plus\Returns\Application\Guard\ReturnRequestGuardInterface;
use Sylius\Plus\Returns\Application\Mapper\ReturnRequestUnitMapper;
use Sylius\Plus\Returns\Application\Mapper\ReturnRequestUnitMapperInterface;
use Sylius\Plus\Returns\Application\Notification\ReturnRequestConfirmationEmailSender;
use Sylius\Plus\Returns\Application\Notification\ReturnRequestNotificationEmailSender;
use Sylius\Plus\Returns\Application\Operator\ReturnInventoryOperator;
use Sylius\Plus\Returns\Application\Operator\ReturnInventoryOperatorInterface;
use Sylius\Plus\Returns\Application\Processor\OrderReplacementProcessor;
use Sylius\Plus\Returns\Application\Provider\Calendar;
use Sylius\Plus\Returns\Application\Provider\NonReturnableOrderItemUnitIdsProvider;
use Sylius\Plus\Returns\Application\Provider\NonReturnableOrderItemUnitIdsProviderInterface;
use Sylius\Plus\Returns\Application\Provider\RequestBasedReturnRequestProvider;
use Sylius\Plus\Returns\Application\Provider\ReturnRequestProviderInterface;
use Sylius\Plus\Returns\Application\Provider\StringReturnRequestResolutionsProvider;
use Sylius\Plus\Returns\Application\StateResolver\ReturnRequestItemsReturnedToInventoryStateResolver;
use Sylius\Plus\Returns\Application\StateResolver\ReturnRequestItemsReturnedToInventoryStateResolverInterface;
use Sylius\Plus\Returns\Application\StateResolver\ReturnRequestPackageReceivedStateResolver;
use Sylius\Plus\Returns\Application\StateResolver\ReturnRequestPackageReceivedStateResolverInterface;
use Sylius\Plus\Returns\Domain\Exception\InvalidReturnRequestResolution;
use Sylius\Plus\Returns\Domain\Exception\OrderNotFound;
use Sylius\Plus\Returns\Domain\Exception\PackackeForReturnRequestCouldNotBeReceived;
use Sylius\Plus\Returns\Domain\Exception\RepairedItemsFromReturnRequestCouldNotBeSent;
use Sylius\Plus\Returns\Domain\Exception\ReturnRequestConfirmationCouldNotBeSent;
use Sylius\Plus\Returns\Domain\Exception\ReturnRequestCouldNotBeResolved;
use Sylius\Plus\Returns\Domain\Exception\ReturnRequestInventoryCouldNotBeReturned;
use Sylius\Plus\Returns\Domain\Exception\ReturnRequestNotFound;
use Sylius\Plus\Returns\Domain\Exception\ReturnRequestNotificationCouldNotBeSent;
use Sylius\Plus\Returns\Domain\Exception\ReturnRequestNotProvided;
use Sylius\Plus\Returns\Domain\Exception\ReturnRequestUnitCouldNotBeMarkedAsReceived;
use Sylius\Plus\Returns\Domain\Exception\ReturnRequestUnitCouldNotBeReturnedToInventory;
use Sylius\Plus\Returns\Domain\Model\OrderInterface;
use Sylius\Plus\Returns\Domain\Model\ReplacementOrder;
use Sylius\Plus\Returns\Domain\Model\ReplacementOrderInterface;
use Sylius\Plus\Returns\Domain\Model\ReplacementOrderItem;
use Sylius\Plus\Returns\Domain\Model\ReplacementOrderItemInterface;
use Sylius\Plus\Returns\Domain\Model\ReturnRequest;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestAwareInterface;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestAwareTrait;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestImage;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestImageInterface;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestInterface;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestPdf;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestResolution;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestResolutions;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestsAllowedAwareInterface;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestsAllowedAwareTrait;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestStates;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestTransitions;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestUnit;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestUnitInterface;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestUnitStates;
use Sylius\Plus\Returns\Domain\Model\ReturnRequestUnitTransitions;
use Sylius\Plus\Returns\Domain\Notification\ReturnRequestConfirmationSender;
use Sylius\Plus\Returns\Domain\Notification\ReturnRequestNotificationSender;
use Sylius\Plus\Returns\Domain\Provider\DateTimeProvider;
use Sylius\Plus\Returns\Domain\Provider\ReturnRequestResolutionsProvider;
use Sylius\Plus\Returns\Infrastructure\Checker\CsrfChecker;
use Sylius\Plus\Returns\Infrastructure\Checker\CsrfCheckerInterface;
use Sylius\Plus\Returns\Infrastructure\Controller\DisplayReturnRequestResolutionFormAction;
use Sylius\Plus\Returns\Infrastructure\DataTransformer\RequestReturnInputCommandDataTransformer;
use Sylius\Plus\Returns\Infrastructure\DataTransformer\ReturnRequestIdInputCommandDataTransformer;
use Sylius\Plus\Returns\Infrastructure\Doctrine\ORM\CountOrderItemUnitsQuery;
use Sylius\Plus\Returns\Infrastructure\Doctrine\ORM\CountOrderItemUnitsQueryInterface;
use Sylius\Plus\Returns\Infrastructure\Doctrine\ORM\ReturnRequestRepository;
use Sylius\Plus\Returns\Infrastructure\Doctrine\ORM\ReturnRequestRepositoryInterface;
use Sylius\Plus\Returns\Infrastructure\Doctrine\ORM\ReturnRequestUnitRepository;
use Sylius\Plus\Returns\Infrastructure\Doctrine\ORM\ReturnRequestUnitRepositoryInterface;
use Sylius\Plus\Returns\Infrastructure\Doctrine\QueryCollectionExtension\ReturnRequestsByShopUserExtension;
use Sylius\Plus\Returns\Infrastructure\Doctrine\QueryItemExtension\OrderMethodsItemExtension;
use Sylius\Plus\Returns\Infrastructure\Fixture\ReturnRequestFixture;
use Sylius\Plus\Returns\Infrastructure\Form\DTO\ReturnRequestOrderItemUnit;
use Sylius\Plus\Returns\Infrastructure\Form\DTO\ReturnRequestOrderItemUnitInterface;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReplacementOrderItemType;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReplacementOrderType;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReturnRequestImageType;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReturnRequestOrderItemUnitType;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReturnRequestResolutionChoiceType;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReturnRequestResolutionType;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReturnRequestSplitType;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReturnRequestType;
use Sylius\Plus\Returns\Infrastructure\Form\Type\ReturnRequestUnitsChoiceType;
use Sylius\Plus\Returns\Infrastructure\Serializer\RequestReturnDenormalizer;
use Sylius\Plus\Returns\Infrastructure\Twig\ReturnRequestExtension;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\AcceptReturnRequestAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\ChangeReturnRequestResolutionAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\CreateReplacementOrderAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\MarkReturnRequestAsRepairedItemsSentAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\MarkReturnRequestUnitsAsReceivedAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\RefundUnitsAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\RejectReturnRequestAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\ResolveReturnRequestAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\ReturnRateMetricAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\ReturnReturnedItemsToInventoryAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\ReturnUnitsToInventoryAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\SelectReturnRequestUnitsAsReceivedAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\SelectUnitsToReturnToInventoryAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Admin\SplitReturnRequestAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Shop\CancelReturnRequestAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Shop\DownloadReturnRequestPdfFileAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Shop\RequestReturnAction;
use Sylius\Plus\Returns\Infrastructure\Ui\Shop\ViewReturnRequestPageAction;
use Sylius\Plus\Returns\Infrastructure\Validator\ReplacementOrderItemInStock;
use Sylius\Plus\Returns\Infrastructure\Validator\ReplacementOrderItemInStockValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\ReplacementOrderUniqueVariants;
use Sylius\Plus\Returns\Infrastructure\Validator\ReplacementOrderUniqueVariantsValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\ResolutionAvailable;
use Sylius\Plus\Returns\Infrastructure\Validator\ResolutionAvailableValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\ReturnRequestAvailable;
use Sylius\Plus\Returns\Infrastructure\Validator\ReturnRequestAvailableValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\ReturnRequestContainsOnlyValidUnits;
use Sylius\Plus\Returns\Infrastructure\Validator\ReturnRequestContainsOnlyValidUnitsValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\ReturnRequestResolutionChangePossibility;
use Sylius\Plus\Returns\Infrastructure\Validator\ReturnRequestResolutionChangePossibilityValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\ReturnUnitPossibility;
use Sylius\Plus\Returns\Infrastructure\Validator\ReturnUnitPossibilityValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\SplitReturnRequestEligibility;
use Sylius\Plus\Returns\Infrastructure\Validator\SplitReturnRequestEligibilityValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\SplitReturnRequestUnitsEligibility;
use Sylius\Plus\Returns\Infrastructure\Validator\SplitReturnRequestUnitsEligibilityValidator;
use Sylius\Plus\Returns\Infrastructure\Validator\SplitReturnRequestUnitsQuantity;
use Sylius\Plus\Returns\Infrastructure\Validator\SplitReturnRequestUnitsQuantityValidator;
use Sylius\Plus\Returns\Tests\Unit\Domain\Model\ReturnRequestAwareTraitTest;
use Sylius\Plus\Returns\Tests\Unit\Domain\Model\ReturnRequestsAllowedAwareTraitTest;
use Sylius\Plus\Returns\Tests\Unit\Infrastructure\Ui\Admin\AcceptReturnRequestActionTest;
use Sylius\Plus\Returns\Tests\Unit\Infrastructure\Ui\Admin\MarkReturnRequestAsRepairedItemsSentActionTest;
use Sylius\Plus\Returns\Tests\Unit\Infrastructure\Ui\Admin\MarkReturnRequestUnitsAsReceivedActionTest;
use Sylius\Plus\Returns\Tests\Unit\Infrastructure\Ui\Admin\RefundUnitsActionTest;
use Sylius\Plus\Returns\Tests\Unit\Infrastructure\Ui\Admin\RejectReturnRequestActionTest;
use Sylius\Plus\Returns\Tests\Unit\Infrastructure\Ui\Admin\ResolveReturnRequestActionTest;
use Sylius\Plus\Returns\Tests\Unit\Infrastucture\Ui\Admin\SessionAwareActionTestCase;
use Sylius\Plus\SharedKernel\Exception\ResourceNotSupportedException;
use Sylius\Plus\SharedKernel\ResourceChannelCheckerInterface;
use Sylius\Plus\Rbac\Tests\Unit\Domain\Model\ToggleablePermissionCheckerTraitTest;
use Sylius\Plus\Rbac\Tests\Unit\Domain\Model\RoleableTraitTest;
use Sylius\Plus\Rbac\Application\Context\AdminUserContextInterface;
use Sylius\Plus\Rbac\Application\Context\AdminUserContext;
use Sylius\Plus\Rbac\Application\Cache\Warmer\PrivilegesCacheWarmer;
use Sylius\Plus\Rbac\Application\Cache\Clearer\PrivilegesCacheClearer;
use Sylius\Plus\Rbac\Application\Privilege\ResourcePrivilege;
use Sylius\Plus\Rbac\Application\Privilege\PrivilegeInterface;
use Sylius\Plus\Rbac\Application\Privilege\CompositePrivilege;
use Sylius\Plus\Rbac\Application\Checker\PermissionChecker;
use Sylius\Plus\Rbac\Application\Checker\AuthorizationCheckerInterface;
use Sylius\Plus\Rbac\Application\Checker\ResourceAuthorizationChecker;
use Sylius\Plus\Rbac\Application\Checker\CompositeAuthorizationChecker;
use Sylius\Plus\Rbac\Application\Exception\AccessDeniedHttpException;
use Sylius\Plus\Rbac\Application\Exception\PrivilegeArgumentNotImplementedException;
use Sylius\Plus\Rbac\Application\Resolver\AdminPermissionResolver;
use Sylius\Plus\Rbac\Application\Resolver\AdminPermissionResolverInterface;
use Sylius\Plus\Rbac\Infrastructure\Form\Extension\AdminUserTypeExtension;
use Sylius\Plus\Rbac\Infrastructure\Form\Type\RoleType;
use Sylius\Plus\Rbac\Infrastructure\Form\Type\RoleTranslationType;
use Sylius\Plus\Rbac\Infrastructure\Fixture\RoleFixture;
use Sylius\Plus\Rbac\Infrastructure\Fixture\Factory\RoleExampleFactory;
use Sylius\Plus\Rbac\Infrastructure\Templating\Helper\AclHelper;
use Sylius\Plus\Rbac\Infrastructure\Doctrine\ORM\RoleRepositoryInterface;
use Sylius\Plus\Rbac\Infrastructure\Doctrine\ORM\RoleRepository;
use Sylius\Plus\Rbac\Infrastructure\Menu\OrderShowMenuBuilder;
use Sylius\Plus\Rbac\Infrastructure\Menu\PermissionExtension;
use Sylius\Plus\Rbac\Infrastructure\Menu\AbstractPermissionChecker;
use Sylius\Plus\Rbac\Infrastructure\Menu\MainMenuBuilder;
use Sylius\Plus\Rbac\Infrastructure\Menu\AdministrationMenuBuilder;
use Sylius\Plus\Rbac\Infrastructure\Menu\CustomerShowMenuBuilder;
use Sylius\Plus\Rbac\Infrastructure\Command\DisableAdministratorPermissionCheckerCommand;
use Sylius\Plus\Rbac\Infrastructure\Twig\HttpKernelExtension;
use Sylius\Plus\Rbac\Infrastructure\Twig\AclExtension;
use Sylius\Plus\Rbac\Infrastructure\Twig\RoutingExtension;
use Sylius\Plus\Rbac\Infrastructure\EventListener\PermissionCheckerListener;
use Sylius\Plus\Rbac\Domain\Model\RoleTranslation;
use Sylius\Plus\Rbac\Domain\Model\RoleInterface;
use Sylius\Plus\Rbac\Domain\Model\RoleableInterface;
use Sylius\Plus\Rbac\Domain\Model\Role;
use Sylius\Plus\Rbac\Domain\Model\AdminUserInterface;
use Sylius\Plus\Rbac\Domain\Model\RoleableTrait;
use Sylius\Plus\Rbac\Domain\Model\RoleTranslationInterface;
use Sylius\Plus\Rbac\Domain\Model\ToggleablePermissionCheckerInterface;
use Sylius\Plus\Rbac\Domain\Model\ToggleablePermissionCheckerTrait;
use Sylius\Plus\Rbac\DependencyInjection\Compiler\CompositeAuthorizationCheckerPass;
use Sylius\Plus\Rbac\DependencyInjection\Compiler\RequestMatcherPass;
use Sylius\Plus\Rbac\DependencyInjection\Compiler\CompositePrivilegePass;
use Sylius\Plus\CustomerPools\Tests\Unit\Infrastructure\Form\Extension\CustomerGuestTypeExtensionTest;
use Sylius\Plus\CustomerPools\Tests\Unit\Infrastructure\Fixture\CustomerPoolFixtureTest;
use Sylius\Plus\CustomerPools\Tests\Unit\Domain\Model\CustomerPoolAwareTraitTest;
use Sylius\Plus\CustomerPools\Application\CommandHandler\Account\SendAccountVerificationEmailHandler;
use Sylius\Plus\CustomerPools\Application\CommandHandler\Account\SendAccountRegistrationEmailHandler;
use Sylius\Plus\CustomerPools\Application\Context\CustomerPoolNotFoundException;
use Sylius\Plus\CustomerPools\Application\Context\CustomerPoolContextInterface;
use Sylius\Plus\CustomerPools\Application\Checker\CustomerResourceChannelChecker;
use Sylius\Plus\CustomerPools\Application\EventListener\ReviewCreateListener;
use Sylius\Plus\CustomerPools\Infrastructure\Form\Extension\CustomerTypeExtension;
use Sylius\Plus\CustomerPools\Infrastructure\Form\Extension\CustomerRegistrationTypeExtension;
use Sylius\Plus\CustomerPools\Infrastructure\Form\Extension\ChannelTypeExtension;
use Sylius\Plus\CustomerPools\Infrastructure\Form\Extension\CustomerGuestTypeExtension;
use Sylius\Plus\CustomerPools\Infrastructure\Form\Type\CustomerPoolChoiceType;
use Sylius\Plus\CustomerPools\Infrastructure\Form\Type\CustomerPoolType;
use Sylius\Plus\CustomerPools\Infrastructure\Context\ChannelBasedCustomerPoolContext;
use Sylius\Plus\CustomerPools\Infrastructure\Validator\CustomerClassMetadataLoader;
use Sylius\Plus\CustomerPools\Infrastructure\Validator\RegisteredUserValidator;
use Sylius\Plus\CustomerPools\Infrastructure\Validator\ApiUniqueReviewerEmailValidator;
use Sylius\Plus\CustomerPools\Infrastructure\Validator\UniqueReviewerEmailValidator;
use Sylius\Plus\CustomerPools\Infrastructure\Validator\UniqueShopUserEmailValidator;
use Sylius\Plus\CustomerPools\Infrastructure\Fixture\CustomerPoolFixture;
use Sylius\Plus\CustomerPools\Infrastructure\Fixture\Factory\CustomerPoolExampleFactory;
use Sylius\Plus\CustomerPools\Infrastructure\Provider\CustomerProviderInterface;
use Sylius\Plus\CustomerPools\Infrastructure\Provider\UsernameAndCustomerPoolProvider;
use Sylius\Plus\CustomerPools\Infrastructure\Doctrine\CustomerLoadClassMetadataListener;
use Sylius\Plus\CustomerPools\Infrastructure\Doctrine\ORM\FindShopUserByUsernameAndCustomerPoolQueryInterface;
use Sylius\Plus\CustomerPools\Infrastructure\Doctrine\ORM\FindShopUserByUsernameAndCustomerPoolQuery;
use Sylius\Plus\CustomerPools\Infrastructure\Menu\AdminMenuListener;
use Sylius\Plus\CustomerPools\Infrastructure\Resolver\CustomerResolverInterface;
use Sylius\Plus\CustomerPools\Infrastructure\Resolver\BaseCustomerResolverInterface;
use Sylius\Plus\CustomerPools\Domain\Model\CustomerPoolAwareInterface;
use Sylius\Plus\CustomerPools\Domain\Model\CustomerPoolInterface;
use Sylius\Plus\CustomerPools\Domain\Model\CustomerPoolAwareTrait;
use Sylius\Plus\CustomerPools\Domain\Model\CustomerPool;
use Sylius\Plus\CustomerPools\Domain\Model\CustomerInterface;
use Sylius\Plus\CustomerPools\Domain\Model\ChannelInterface;
use Sylius\Plus\BusinessUnits\Tests\Unit\Infrastructure\Fixture\BusinessUnitFixtureTest;
use Sylius\Plus\BusinessUnits\Tests\Unit\Domain\Model\BusinessUnitAwareTraitTest;
use Sylius\Plus\BusinessUnits\Infrastructure\Form\Type\BusinessUnitChoiceType;
use Sylius\Plus\BusinessUnits\Infrastructure\Form\Type\BusinessUnitAddressType;
use Sylius\Plus\BusinessUnits\Infrastructure\Form\Type\BusinessUnitType;
use Sylius\Plus\BusinessUnits\Infrastructure\Fixture\BusinessUnitFixture;
use Sylius\Plus\BusinessUnits\Infrastructure\Fixture\Factory\BusinessUnitExampleFactory;
use Sylius\Plus\BusinessUnits\Infrastructure\Fixture\Factory\BusinessUnitAddressExampleFactory;
use Sylius\Plus\BusinessUnits\Domain\Model\BusinessUnitAddress;
use Sylius\Plus\BusinessUnits\Domain\Model\BusinessUnitAwareInterface;
use Sylius\Plus\BusinessUnits\Domain\Model\BusinessUnit;
use Sylius\Plus\BusinessUnits\Domain\Model\BusinessUnitAwareTrait;
use Sylius\Plus\BusinessUnits\Domain\Model\BusinessUnitInterface;
use Sylius\Plus\BusinessUnits\Domain\Model\BusinessUnitAddressInterface;
use Sylius\Plus\Loyalty\Tests\Unit\Infrastructure\Form\Type\Action\ChannelsBasedItemsTotalToPointsRatioConfigurationTypeTest;


/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_address")
 */
#[ORM\Entity]
#[ORM\Table(name: 'sylius_address')]
class Address extends BaseAddress implements LastLoginIpAwareInterface
{
    use LastLoginIpAwareTrait;
    use ToggleablePermissionCheckerTrait;

    /**
     * @var VariantsQuantityMapFactoryInterface
     * @var ToggleablePermissionCheckerTraitTest
     * @var RoleableTraitTest
     * @var AdminUserContextInterface
     * @var PrivilegesCacheWarmer
     * @var PrivilegesCacheClearer
     * @var ResourcePrivilege
     * @var PrivilegeInterface
     * @var CompositePrivilege
     * @var AuthorizationCheckerInterface
     * @var CompositeAuthorizationChecker
     * @var AccessDeniedHttpException
     * @var PrivilegeArgumentNotImplementedException
     * @var AdminPermissionResolver
     * @var AdminPermissionResolverInterface
     * @var AdminUserTypeExtension
     * @var RoleType
     * @var RoleTranslationType
     * @var RoleFixture
     * @var RoleExampleFactory
     * @var AclHelper
     * @var RoleRepositoryInterface
     * @var RoleRepository
     * @var OrderShowMenuBuilder
     * @var PermissionExtension
     * @var AbstractPermissionChecker
     * @var MainMenuBuilder
     * @var AdministrationMenuBuilder
     * @var CustomerShowMenuBuilder
     * @var DisableAdministratorPermissionCheckerCommand
     * @var HttpKernelExtension
     * @var AclExtension
     * @var RoutingExtension
     * @var PermissionCheckerListener
     * @var RoleTranslation
     * @var RoleableInterface
     * @var AdminUserInterface
     * @var Role
     * @var RoleableTrait
     * @var RoleTranslationInterface
     * @var ToggleablePermissionCheckerInterface
     * @var CompositeAuthorizationCheckerPass
     * @var RequestMatcherPass
     * @var CompositePrivilegePass
     * @var CustomerGuestTypeExtensionTest
     * @var CustomerPoolFixtureTest
     * @var CustomerPoolAwareTraitTest
     * @var SendAccountVerificationEmailHandler
     * @var SendAccountRegistrationEmailHandler
     * @var CustomerPoolNotFoundException
     * @var CustomerPoolContextInterface
     * @var ReviewCreateListener
     * @var CustomerTypeExtension
     * @var CustomerRegistrationTypeExtension
     * @var ChannelTypeExtension
     * @var CustomerGuestTypeExtension
     * @var CustomerPoolChoiceType
     * @var CustomerPoolType
     * @var ChannelBasedCustomerPoolContext
     * @var CustomerClassMetadataLoader
     * @var RegisteredUserValidator
     * @var ApiUniqueReviewerEmailValidator
     * @var UniqueReviewerEmailValidator
     * @var UniqueShopUserEmailValidator
     * @var CustomerPoolFixture
     * @var CustomerPoolExampleFactory
     * @var UsernameAndCustomerPoolProvider
     * @var CustomerLoadClassMetadataListener
     * @var FindShopUserByUsernameAndCustomerPoolQueryInterface
     * @var FindShopUserByUsernameAndCustomerPoolQuery
     * @var AdminMenuListener
     * @var CustomerResolverInterface
     * @var CustomerPoolAwareInterface
     * @var CustomerPoolInterface
     * @var CustomerPoolAwareTrait
     * @var CustomerPool
     * @var CustomerInterface
     * @var ChannelInterface
     * @var BusinessUnitFixtureTest
     * @var BusinessUnitAwareTraitTest
     * @var BusinessUnitChoiceType
     * @var BusinessUnitAddressType
     * @var BusinessUnitType
     * @var BusinessUnitFixture
     * @var BusinessUnitExampleFactory
     * @var BusinessUnitAddressExampleFactory
     * @var BusinessUnitAddress
     * @var BusinessUnitAwareInterface
     * @var BusinessUnit
     * @var BusinessUnitAwareTrait
     * @var BusinessUnitInterface
     * @var BusinessUnitAddressInterface
     * @var ChannelsBasedItemsTotalToPointsRatioConfigurationTypeTest
     * @var ToggleablePermissionCheckerTraitTest
     * @var ChannelsBasedPointsPerProductRatioConfigurationTypeTest
     * @var LoyaltyRuleActionTypeTest
     * @var LoyaltyRuleFixtureTest
     * @var LoyaltyPurchaseFixtureTest
     * @var LoyaltyAwareTraitTest
     * @var LoyaltyRuleActionInterface
     * @var LoyaltyRuleAction
     * @var BuyLoyaltyPurchaseHandler
     * @var LoyaltyPointsTransactionLogger
     * @var LoyaltyPointsTransactionLoggerInterface
     * @var LoyaltyActionTypes
     * @var ActionBasedLoyaltyPointsCalculatorInterface
     * @var ConfigurationBasedLoyaltyPointsCalculatorInterface
     * @var ChannelsBasedItemsTotalToPointsRatioCalculator
     * @var DelegatingLoyaltyPointsCalculator
     * @var ChannelsBasedPointsPerProductRatioCalculator
     * @var LoyaltyPointsAccountModifier
     * @var LoyaltyPointsAccountModifierInterface
     * @var LoyaltyPointsProcessor
     * @var OrdersLoyaltyPointsProvider
     * @var LoyaltyPointsAccountProvider
     * @var OrdersLoyaltyPointsProviderInterface
     * @var LoyaltyPointsAccountProviderInterface
     * @var Emails
     * @var LoyaltyPointsAccountDataProvider
     * @var LoyaltyPurchasePromotionCouponInstructionGenerator
     * @var LoyaltyPurchasePromotionCouponInstructionGeneratorInterface
     * @var CustomerEmailAwareInterface
     * @var BuyLoyaltyPurchase
     * @var LoyaltyPointsAssignerInterface
     * @var LoyaltyPointsAssigner
     * @var LoyaltyRuleActionFactoryInterface
     * @var LoyaltyRuleActionFactory
     * @var BuyLoyaltyPurchaseAction
     * @var ChannelBasedRatioConfigurationDataMapper
     * @var ChangeActionToChannelsBasedRatioConfigurationTransformer
     * @var NullableIdentifierToResourceTransformer
     * @var LoyaltyRuleActionType
     * @var ProductPerChannelAutocompleteChoiceType
     * @var LoyaltyPurchaseType
     * @var LoyaltyRuleType
     * @var ChannelsBasedPointsPerProductRatioConfigurationType
     * @var ItemsTotalToPointsRatioConfigurationType
     * @var ChannelsBasedItemsTotalToPointsRatioConfigurationType
     * @var PointsPerProductRatioConfigurationType
     * @var LoyaltyRuleActionTypeChoiceType
     * @var CouponBasedPromotionChoiceType
     * @var OrderNormalizer
     * @var LoyaltyRuleDenormalizer
     * @var PointsPerProductRatioConfigurationEligibility
     * @var RegisteredRuleActionType
     * @var SufficientPointsForPurchaseValidator
     * @var PromotionEligibilityValidator
     * @var RegisteredRuleActionTypeValidator
     * @var SufficientPointsForPurchase
     * @var PurchaseEnabledInChannel
     * @var PromotionEligibility
     * @var PurchaseEnabledInChannelValidator
     * @var PointsPerProductRatioConfigurationEligibilityValidator
     * @var LoyaltyRuleFixture
     * @var LoyaltyRuleActionExampleFactory
     * @var LoyaltyPurchaseExampleFactory
     * @var LoyaltyRuleExampleFactory
     * @var LoyaltyPurchaseFixture
     * @var LoyaltyRuleActionDataTransformerInterface
     * @var ItemsTotalToPointsRatioConfigurationDataTransformer
     * @var LoyaltyRuleActionInputDataTransformer
     * @var PointsPerProductRatioDataTransformer
     * @var CustomerEmailAwareCommandDataTransformer
     * @var EnabledLoyaltyPurchasesByChannelExtension
     * @var ChannelRestrictingEnabledLoyaltyPurchaseListQueryBuilderInterface
     * @var ChannelRestrictingEnabledLoyaltyPurchaseListQueryBuilder
     * @var LoyaltyRuleQueryInterface
     * @var LoyaltyRuleQuery
     * @var ShopAccountMenuListener
     * @var LoyaltyAdminMenuListener
     * @var LoyaltyExtension
     * @var LoyaltyPointsTransactionTypes
     * @var LoyaltyAwareTrait
     * @var LoyaltyPointsAccountInterface
     * @var LoyaltyAwareInterface
     * @var LoyaltyRuleActionInterface
     * @var AdjustmentType
     * @var LoyaltyPurchase
     * @var LoyaltyRuleAction
     * @var CustomerInterface
     * @var LoyaltyRuleInterface
     * @var LoyaltyPointsTransactionInterface
     * @var LoyaltyPointsAccount
     * @var LoyaltyPointsTransaction
     * @var LoyaltyPurchaseInterface
     * @var LoyaltyRuleConfigurationInterface
     * @var ChannelsBasedPointsPerProductRatioConfiguration
     * @var ItemsTotalToPointsRatioConfiguration
     * @var PointsPerProductRatioConfiguration
     * @var ChannelsBasedItemsTotalToPointsRatioConfiguration
     * @var ChannelBasedLoyaltyRuleConfigurationInterface
     * @var LoyaltyRule
     * @var LoyaltyRuleActionsPass
     * @var PartialShipmentCreator
     * @var SplitAndSendShipmentCommandCreator
     * @var SplitAndSendShipmentCommandCreatorInterface
     * @var PartialShipmentCreatorInterface
     * @var SplitAndSendShipmentHandler
     * @var OrderShipmentPurifierInterface
     * @var OrderShipmentPurifier
     * @var SplitAndSendShipment
     * @var ShipmentTransitions
     * @var ShipmentFactory
     * @var ShipmentFactoryInterface
     * @var AdjustmentDuplicator
     * @var AdjustmentDuplicatorInterface
     * @var OrderItemUnitsArrayToOrderItemUnitIdsCollectionTransformer
     * @var ShippingUnitsChoiceType
     * @var PartialShipType
     * @var SplitShipmentUnitsEligibility
     * @var SplitShipmentUnitsEligibilityValidator
     * @var ShipmentUnitModifier
     * @var ShipmentUnitModifierInterface
     * @var ShipmentSplitterInterface
     * @var InventorySourceShipmentSplitter
     * @var ShipmentUnitsSplitter
     * @var ShipmentUnitsSplitterInterface
     * @var InventorySourceShipmentSplitterInterface
     * @var ShipmentSplitter
     * @var InventorySourceStockUpdaterTest
     * @var InventorySourceTest
     * @var InventorySourceAwareTraitTest
     * @var InventorySourceStocksAwareTraitTest
     * @var ModifyInventorySourceStockHandler
     * @var AvailabilityChecker
     * @var VariantQuantityMapAvailabilityChecker
     * @var IsStockSufficientCheckerInterface
     * @var VariantAvailabilityChecker
     * @var IsStockSufficientChecker
     * @var AvailabilityCheckerInterface
     * @var VariantQuantityMapAvailabilityCheckerInterface
     * @var VariantAvailabilityCheckerInterface
     * @var InsufficientItemFromOrderItemsProvider
     * @var AvailableInventorySourcesProvider
     * @var InsufficientItemFromOrderItemsProviderInterface
     * @var AvailableInventorySourcesProviderInterface
     * @var InventorySourceStockUpdaterInterface
     * @var InventorySourceStockUpdater
     * @var OrderItemController
     * @var InventorySourceStockDataPersister
     * @var ProductVariantDataPersister
     * @var InventorySourceDataPersister
     * @var ModifyInventorySourceStock
     * @var PriorityInventorySourcesFilter
     * @var SufficientInventorySourcesFilter
     * @var InventorySourcesFilterInterface
     * @var EnabledChannelInventorySourcesFilter
     * @var ShipmentInventorySourceAssigner
     * @var ShipmentInventorySourceAssignerInterface
     * @var InventorySourceFactoryInterface
     * @var InventorySourceFactory
     * @var HoldOrderInventoryOperatorInterface
     * @var HoldOrderInventoryOperator
     * @var InventoryOperatorInterface
     * @var CancelOrderInventoryOperatorInterface
     * @var ShipmentInventoryOperator
     * @var ChangeInventorySourceOperator
     * @var CancelOrderInventoryOperator
     * @var ShipShipmentInventoryOperatorInterface
     * @var ShipShipmentInventoryOperator
     * @var InventoryOperator
     * @var ChangeInventorySourceOperatorInterface
     * @var ShipmentInventoryOperatorInterface
     * @var InventorySourceResolver
     * @var InventorySourceResolverInterface
     * @var InventorySourceDeletionListener
     * @var ChannelCreateListener
     * @var SplitShipmentAction
     * @var ChangeInventorySourceStockOnHandAction
     * @var ProductVariantTypeExtension
     * @var InventorySourceStockType
     * @var InventorySourceStockOnHandType
     * @var InventorySourceType
     * @var InventorySourceChoiceType
     * @var InventorySourceAddressType
     * @var InventorySourceCollectionType
     * @var CartItemAvailability
     * @var StockOnHandCannotBeLowerThanOnHold
     * @var NoInventorySourceForTrackedItemUnits
     * @var OrderInStockValidator
     * @var OrderInStock
     * @var NoInventorySourceForTrackedItemUnitsValidator
     * @var StockOnHandCannotBeLowerThanOnHoldValidator
     * @var CartItemAvailabilityValidator
     * @var StockSufficientForInventorySource
     * @var OrderItemClassMetadataLoader
     * @var StockSufficientForInventorySourceValidator
     * @var InventorySourceStockFixture
     * @var InventorySourceFixture
     * @var InventorySourceExampleFactory
     * @var FindAllDescendantProductsByTaxonQueryInterface
     * @var InventorySourceStockRepositoryInterface
     * @var InventorySourceStockRepository
     * @var FindAllDescendantProductsByTaxonQuery
     * @var AdminMenuListener
     * @var InventoryExtension
     * @var InventorySourceAwareInterface
     * @var InventorySourceAddressInterface
     * @var InventorySourceStocksAwareTrait
     * @var InventoryAwareInterface
     * @var InventorySourceStocksAwareInterface
     * @var VariantsQuantityMap
     * @var ShipmentInterface
     * @var VariantsQuantityMapInterface
     * @var InventorySourceStockInterface
     * @var InventorySourceStock
     * @var InventorySourceAddress
     * @var InventorySourceInterface
     * @var InventorySourceAwareTrait
     * @var InventorySource
     * @var ProductVariantInterface
     * @var VariantQuantityNotSpecified
     * @var InventorySourceStockInUseException
     * @var InventorySourceStockNotFoundException
     * @var VariantQuantityAlreadySpecified
     * @var UnresolvedInventorySource
     * @var InventoryPass
     * @var MarkReturnRequestAsRepairedItemsSentActionTest
     * @var MarkReturnRequestUnitsAsReceivedActionTest
     * @var RefundUnitsActionTest
     * @var AcceptReturnRequestActionTest
     * @var ResolveReturnRequestActionTest
     * @var RejectReturnRequestActionTest
     * @var SessionAwareActionTestCase
     * @var ReturnRequestAwareTraitTest
     * @var ReturnRequestsAllowedAwareTraitTest
     * @var ChangeReturnRequestResolutionCommandCreator
     * @var ReplacementOrderCreatorInterface
     * @var ChangeReturnRequestResolutionCommandCreatorInterface
     * @var ReplacementOrderCreator
     * @var MarkReturnRequestAsItemsReturnedToInventoryHandler
     * @var AcceptReturnRequestHandler
     * @var CreateReplacementOrderHandler
     * @var MarkReturnRequestAsPackageReceivedHandler
     * @var ResolveReturnRequestHandler
     * @var RejectReturnRequestHandler
     * @var ReturnUnitsToInventoryHandler
     * @var CancelReturnRequestHandler
     * @var SplitReturnRequestHandler
     * @var RequestReturnHandler
     * @var ChangeReturnRequestResolutionHandler
     * @var MarkReturnRequestAsRepairedItemsSentHandler
     * @var SendReturnRequestConfirmationHandler
     * @var SendReturnRequestSplitInformationHandler
     * @var MarkReturnRequestUnitsAsReceivedHandler
     * @var DelegatingCalculator
     * @var ReturnRateMetricCalculatorInterface
     * @var ReturnRateMetricCalculator
     * @var ReturnRequestCustomerRelationCheckerInterface
     * @var OrderItemsAvailabilityCheckerInterface
     * @var ReturnRequestAllUnitsReturnedToInventoryChecker
     * @var ReturnRequestAllUnitsReceivedChecker
     * @var ReturnRequestCustomerRelationChecker
     * @var ReturnRequestAllUnitsReceivedCheckerInterface
     * @var ReturnRequestAllUnitsReturnedToInventoryCheckerInterface
     * @var OrderItemsAvailabilityChecker
     * @var ReturnRequestResourceChannelChecker
     * @var ReturnRequestNotificationEmailSender
     * @var ReturnRequestConfirmationEmailSender
     * @var OrderReplacementProcessor
     * @var ReturnRequestUnitMapper
     * @var ReturnRequestUnitMapperInterface
     * @var NonReturnableOrderItemUnitIdsProviderInterface
     * @var StringReturnRequestResolutionsProvider
     * @var NonReturnableOrderItemUnitIdsProvider
     * @var ReturnRequestProviderInterface
     * @var RequestBasedReturnRequestProvider
     * @var Calendar
     * @var Emails
     * @var ReturnableOrderItemUnitsController
     * @var AvailableReturnRequestResolutionsController
     * @var ReturnRequestPdfFileGenerator
     * @var ReturnRequestPdfFileGeneratorInterface
     * @var SequentialReturnRequestNumberGenerator
     * @var ReturnRequestNumberGenerator
     * @var MarkReturnRequestAsPackageReceived
     * @var ChangeReturnRequestResolution
     * @var MarkReturnRequestUnitsAsReceived
     * @var MarkReturnRequestAsItemsReturnedToInventory
     * @var ResolveReturnRequest
     * @var CancelReturnRequest
     * @var MarkReturnRequestAsRepairedItemsSent
     * @var AcceptReturnRequest
     * @var RequestReturn
     * @var SendReturnRequestSplitInformation
     * @var ReturnUnitsToInventory
     * @var ReturnRequestUnitReturnToInventoryInterface
     * @var ReturnRequestUnitReturnToInventory
     * @var SendReturnRequestConfirmation
     * @var RejectReturnRequest
     * @var SplitReturnRequest
     * @var CreateReplacementOrder
     * @var ReturnRequestGuard
     * @var ReturnRequestGuardInterface
     * @var InsufficientStockWhileCreatingReplacementOrder
     * @var ReturnRequestNotAccessible
     * @var ReturnRequestFactoryInterface
     * @var ReturnRequestFactory
     * @var ReplacementOrderFactory
     * @var ReplacementOrderFactoryInterface
     * @var ReplacementOrderItemFactory
     * @var ReturnRequestUnitFactoryInterface
     * @var ReturnRequestUnitFactory
     * @var ReplacementOrderItemFactoryInterface
     * @var ReturnInventoryOperatorInterface
     * @var ReturnInventoryOperator
     * @var ReturnRequestItemsReturnedToInventoryStateResolverInterface
     * @var ReturnRequestPackageReceivedStateResolver
     * @var ReturnRequestItemsReturnedToInventoryStateResolver
     * @var ReturnRequestPackageReceivedStateResolverInterface
     * @var CancelReturnRequestAction
     * @var DownloadReturnRequestPdfFileAction
     * @var RequestReturnAction
     * @var ViewReturnRequestPageAction
     * @var DownloadReturnRequestPdfFileAction
     * @var RejectReturnRequestAction
     * @var ReturnUnitsToInventoryAction
     * @var AcceptReturnRequestAction
     * @var RefundUnitsAction
     * @var ReturnReturnedItemsToInventoryAction
     * @var MarkReturnRequestUnitsAsReceivedAction
     * @var ResolveReturnRequestAction
     * @var SelectUnitsToReturnToInventoryAction
     * @var CreateReplacementOrderAction
     * @var ReturnRateMetricAction
     * @var ChangeReturnRequestResolutionAction
     * @var SplitReturnRequestAction
     * @var SelectReturnRequestUnitsAsReceivedAction
     * @var MarkReturnRequestAsRepairedItemsSentAction
     * @var ReturnRequestInterface
     * @var ReturnRequestOrderItemUnitInterface
     * @var ReturnRequest
     * @var ReturnRequestOrderItemUnit
     * @var ReturnRequestFactoryInterface
     * @var ReturnRequestFactory
     * @var ChannelTypeExtension
     * @var ReplacementOrderType
     * @var ReturnRequestType
     * @var ReturnRequestUnitsChoiceType
     * @var ReturnRequestImageType
     * @var ReturnRequestOrderItemUnitType
     * @var ReplacementOrderItemType
     * @var ReturnRequestSplitType
     * @var ReturnRequestResolutionType
     * @var ReturnRequestResolutionChoiceType
     * @var RequestReturnDenormalizer
     * @var ReturnRequestAvailable
     * @var SplitReturnRequestEligibilityValidator
     * @var ReplacementOrderUniqueVariants
     * @var ReturnRequestContainsOnlyValidUnitsValidator
     * @var ReturnUnitPossibilityValidator
     * @var ReturnRequestContainsOnlyValidUnits
     * @var SplitReturnRequestEligibility
     * @var ReplacementOrderItemInStock
     * @var SplitReturnRequestUnitsQuantity
     * @var ReturnRequestAvailableValidator
     * @var SplitReturnRequestUnitsEligibility
     * @var ReplacementOrderUniqueVariantsValidator
     * @var ReplacementOrderItemInStockValidator
     * @var ReturnRequestResolutionChangePossibility
     * @var ReturnRequestResolutionChangePossibilityValidator
     * @var ResolutionAvailableValidator
     * @var ResolutionAvailable
     * @var SplitReturnRequestUnitsEligibilityValidator
     * @var SplitReturnRequestUnitsQuantityValidator
     * @var ReturnUnitPossibility
     * @var ReturnRequestFixture
     * @var CsrfChecker
     * @var CsrfCheckerInterface
     * @var ReturnRequestIdInputCommandDataTransformer
     * @var RequestReturnInputCommandDataTransformer
     * @var DisplayReturnRequestResolutionFormAction
     * @var OrderMethodsItemExtension
     * @var OrderShopUserItemExtension
     * @var ReturnRequestsByShopUserExtension
     * @var ReturnRequestRepository
     * @var ReturnRequestUnitRepository
     * @var ReturnRequestUnitRepositoryInterface
     * @var CountOrderItemUnitsQueryInterface
     * @var ReturnRequestRepositoryInterface
     * @var CountOrderItemUnitsQuery
     * @var AdminMenuListener
     * @var ReturnRequestExtension
     * @var ReturnRequestNotificationSender
     * @var ReturnRequestConfirmationSender
     * @var ReturnRequestResolutionsProvider
     * @var DateTimeProvider
     * @var ReturnRequestPdf
     * @var OrderInterface
     * @var ReturnRequestsAllowedAwareInterface
     * @var ReturnRequestInterface
     * @var ReturnRequestUnitTransitions
     * @var ReturnRequestImageInterface
     * @var ReturnRequestUnitInterface
     * @var ReplacementOrderItemInterface
     * @var ReturnRequestStates
     * @var ReturnRequestAwareTrait
     * @var AdjustmentType
     * @var ReturnRequestUnit
     * @var ReturnRequestResolution
     * @var ReturnRequestResolutions
     * @var ReturnRequest
     * @var ReplacementOrderInterface
     * @var ReplacementOrderItem
     * @var ReplacementOrder
     * @var ReturnRequestUnitStates
     * @var ReturnRequestImage
     * @var ReturnRequestsAllowedAwareTrait
     * @var ChannelInterface
     * @var ReturnRequestAwareInterface
     * @var ReturnRequestTransitions
     * @var PackackeForReturnRequestCouldNotBeReceived
     * @var ReturnRequestConfirmationCouldNotBeSent
     * @var ReturnRequestNotProvided
     * @var ReturnRequestUnitCouldNotBeMarkedAsReceived
     * @var InvalidReturnRequestResolution
     * @var ReturnRequestNotificationCouldNotBeSent
     * @var ReturnRequestNotFound
     * @var RepairedItemsFromReturnRequestCouldNotBeSent
     * @var ReturnRequestCouldNotBeResolved
     * @var ReturnRequestUnitCouldNotBeReturnedToInventory
     * @var OrderNotFound
     * @var ReturnRequestInventoryCouldNotBeReturned
     * @var AdminChannelAwareTraitTest
     * @var ResourceChannelChecker
     * @var ResourceChannelEnabilibityCheckerInterface
     * @var ChannelAwareResourceChannelChecker
     * @var ProductVariantResourceChannelChecker
     * @var ResourceChannelEnabilibityChecker
     * @var ChannelsAwareResourceChannelChecker
     * @var OrderAwareResourceChannelChecker
     * @var AvailableChannelsForAdminProvider
     * @var AdminChannelProviderInterface
     * @var AdminChannelProvider
     * @var SingleResourceProvider
     * @var AvailableChannelsForAdminProviderInterface
     * @var ResourcesCollectionProvider
     * @var ChannelRestrictingNewResourceFactory
     * @var AdminUserChannelEnableListener
     * @var AdminUserTypeExtension
     * @var ChannelRestrictingProductTypeExtension
     * @var ChannelRestrictingProductVariantGenerationTypeExtension
     * @var ChannelRestrictingProductVariantTypeExtension
     * @var CustomerListQueryBuilder
     * @var ChannelRestrictingProductListQueryBuilderInterface
     * @var FindLatestCustomersQueryTrait
     * @var CreditMemoListQueryBuilder
     * @var InvoiceListQueryBuilderInterface
     * @var ChannelRestrictingProductListQueryBuilder
     * @var CreateOrderListQueryBuilderTrait
     * @var CustomerListQueryBuilderInterface
     * @var CreateShipmentListQueryBuilderTrait
     * @var InvoiceListQueryBuilder
     * @var FindProductsByChannelAndPhraseQueryInterface
     * @var CreditMemoListQueryBuilderInterface
     * @var FindProductsByChannelAndPhraseQuery
     * @var CreatePaymentListQueryBuilderTrait
     * @var AdminUserChannelFilter
     * @var AdminChannelExtension
     * @var AdminChannelAwareTrait
     */
    private VariantsQuantityMapFactoryInterface $variantsQuantityMapFactory;

    public function __construct(VariantsQuantityMapFactoryInterface $variantsQuantityMapFactory)
    {
        $this->variantsQuantityMapFactory = $variantsQuantityMapFactory;
    }

    public function handleDashboard(DashboardController $controller): bool
    {
        return $controller ? true : false;
    }

    public function createVariantsQuantityMap(VariantsQuantityMapFactory $factory): bool
    {
        return $factory ? true : false;
    }

    public function splitShipment(SplitShipmentAction $splitShipmentAction): void
    {
        $splitShipmentAction;
    }

    public function validateStock(StockSufficientForInventorySource $sourceValidator): bool
    {
        return $sourceValidator ? true : false;
    }

    public function validateStockSource(
        StockSufficientForInventorySourceValidator $sourceValidator
    ): bool {
        return $sourceValidator ? true : false;
    }

    public function returnUnitsToInventory(ReturnUnitsToInventoryAction $action): void
    {
        $action;
    }

    public function selectUnitsToReturn(SelectUnitsToReturnToInventoryAction $action): void
    {
        $action;
    }

    public function checkResourceChannel(
        ResourceChannelCheckerInterface $resourceChecker
    ): bool {
        return $resourceChecker ? true : false;
    }

    public function authorizeResource(ResourceAuthorizationChecker $checker): bool
    {
        return $checker ? true : false;
    }

    public function validatePermission(PermissionChecker $checker): bool
    {
        return $checker ? true : false;
    }

    public function adminContextExample(AdminUserContext $context): string
    {
        return $context->getAdminUser()->getUsername();
    }

    public function handleRoleInterface(RoleInterface $role): string
    {
        return $role->getName();
    }

    public function checkCustomerResourceChannel(CustomerResourceChannelChecker $checker): bool
    {
        return $checker ? true : false;
    }

    public function handleResourceException(
        ResourceNotSupportedException $exception
    ): string {
        return $exception->getMessage();
    }
}
