// assets/admin/controllers/delete-trigger.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  // Target the element that has the delete-taxon controller
  static targets = ['deleteElement'];

  // Method to trigger the delete
  triggerDelete() {
    // Get the delete-taxon controller instance
    const deleteController = this.application.getControllerForElementAndIdentifier(
      this.deleteElementTarget,
      '@sylius/admin-bundle/delete-taxon'
    );

    if (deleteController) {
      // Manually trigger the modal open
      deleteController.element.dispatchEvent(
        new CustomEvent('sylius_admin:taxon:open_delete_modal', {
          detail: {
            csrfToken: this.deleteElementTarget.querySelector('[data-delete-taxon-csrf-token-target]').value,
            taxonId: this.deleteElementTarget.dataset.taxonId
          },
          bubbles: true
        })
      );
    } else {
      console.error('Delete taxon controller not found!');
    }
  }
}
