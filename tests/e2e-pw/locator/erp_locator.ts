import { Locator, Page } from "@playwright/test";

export class ErpLocators {

    readonly page: Page;

    /**
     *  Plugin Install/Uninstall  
     */

    readonly pluginSyncButton: Locator;
    readonly pluginthreeDot: Locator;
    readonly pluginName : Locator;
    readonly pluginInstallButton: Locator;
    readonly pluginUninstallButton: Locator
    readonly pluginConfirmButton : Locator;
    readonly pluginSearchInput : Locator;
    readonly pluginSuccessMessage : Locator;
    readonly pluginErrorMessage : Locator;

    /**
     * Companies
     */

    readonly allCompaniesCount: Locator;
    readonly companiesMenuLink: Locator;
    readonly companiesTable: Locator;
    readonly companiesCreateButton: Locator;
    readonly companiesNameInput: Locator;
    readonly companiesEmailInput: Locator;
    readonly companiesPhoneInput: Locator;
    readonly companiesStatusToggleOn: Locator;
    readonly companiesStatusToggleOff: Locator;
    readonly companiesSaveButton: Locator;
    readonly companiesSearchInput: Locator;
    readonly companiesRowActionsButton: Locator;
    readonly companiesEditButton: Locator;
    readonly companiesDeleteButton: Locator;
    readonly selectAllCompaniesButton: Locator;
    readonly bulkActionsButton: Locator;
    readonly forceDeleteButton: Locator;
    readonly companiesConfirmDeleteButton: Locator;
    readonly companiesStatusToggle: Locator;
    readonly companiesSuccessToast: Locator;
    readonly companiesErrorToast: Locator;
    readonly companiesFeildValidationMessage: Locator;
    readonly companiesValidationMessage: Locator;

    /**
     * Users
     */

    readonly usersMenuLink: Locator;
    readonly allUsersCount: Locator;
    readonly usersTable: Locator;
    readonly usersCreateButton: Locator;
    readonly usersInviteButton: Locator;
    readonly usersNameInput: Locator;
    readonly usersEmailInput: Locator;
    readonly usersPasswordInput: Locator;
    readonly usersPasswordConfirmationInput: Locator;
    readonly usersRoleSelect: Locator;
    readonly usersCompanySelect: Locator;
    readonly usersCompanySearchInput: Locator;
    readonly usersRoleOption: Locator;
    readonly usersCompanyOption: Locator;
    readonly usersSaveButton: Locator;
    readonly usersSearchInput: Locator;
    readonly usersRowActionsButton: Locator;
    readonly usersEditButton: Locator;
    readonly usersDeleteButton: Locator;
    readonly usersConfirmDeleteButton: Locator;
    readonly selectAllUsersButton: Locator;
    readonly usersBulkActionsButton: Locator;
    readonly usersForceDeleteButton: Locator;
    readonly usersStatusToggle: Locator;
    readonly usersCreateStatusToggle: Locator;
    readonly usersResetPasswordButton: Locator;
    readonly usersChangePasswordInput: Locator;
    readonly usersChangePasswordConfirmationInput: Locator;
    readonly usersChangePasswordSaveButton: Locator;
    readonly userMenuButton: Locator;
    readonly logoutButton: Locator;
    readonly usersSuccessToast: Locator;
    readonly usersErrorToast: Locator;
    readonly userFeildValidationMessage: Locator;
    readonly usersValidationMessage: Locator;
    readonly manageUsersEnableResetCard: Locator;
    readonly manageUsersEnableResetToggle: Locator;
    readonly manageUsersEnableInvitationToggle: Locator;
    readonly settingsSaveButton: Locator;

    /**
     * Sales - Customers, Products, Quotations
     */

    readonly salesCustomersTable: Locator;
    readonly salesCustomerCreateButton: Locator;
    readonly salesCustomerNewCreateButton: Locator;
    readonly salesCustomerNameInput: Locator;
    readonly salesCustomerEmailInput: Locator;
    readonly salesCustomerSaveButton: Locator;
    readonly salesCustomerSearchInput: Locator;
    readonly salesCustomerEditButton: Locator;
    readonly salesCustomerDeleteButton: Locator;


    readonly salesProductsTable: Locator;
    readonly salesProductNewCreateButton: Locator;
    readonly salesProductNameInput: Locator;
    readonly salesProductCategorySelect: Locator;
    readonly salesProductPriceInput: Locator;
    readonly salesProductUomSelect: Locator;
    readonly salesProductSaveButton: Locator;
    readonly salesProductCreateButton: Locator;
    readonly salesProductEditButton: Locator;
    readonly salesProductDeleteButton: Locator;

    readonly salesQuotationCreateButton: Locator;
    readonly salesQuotationEditButton: Locator;
    readonly salesQuotationCustomerSelect: Locator;
    readonly salesQuotationPaymentTermSelect: Locator;
    readonly salesQuotationAddProductButton: Locator;
    readonly salesQuotationProductSelectInput: Locator;
    readonly salesQuotationQuantityInput: Locator;
    readonly salesQuotationSaveButton: Locator;
    readonly salesQuotationDeleteButton: Locator;
    readonly salesQuotationConfirmButton: Locator;
    readonly salesQuotationSendButton: Locator;
    readonly salesQuotationSendSubmitButton: Locator;
    readonly salesQuotationSentRadio: Locator;
    readonly salesQuotationCreateInvoiceButton: Locator;
    readonly salesQuotationInvoiceSubmitButton: Locator;
    readonly salesQuotationDeliveriesTable: Locator;
    readonly salesQuotationDeliveryEditButton: Locator;
    readonly salesDeliveryValidateButton: Locator;
    readonly salesDeliveryNoBackorderButton: Locator;
    readonly salesInvoicesTable: Locator;

    readonly salesSearchInput: Locator;
    readonly salesRowActionsButton: Locator;
    readonly salesEditAction: Locator;
    readonly salesDeleteAction: Locator;
    readonly salesConfirmDeleteButton: Locator;

    readonly salesSelectSearchInput: Locator;
    readonly salesSelectOption: Locator;
    readonly salesSuccessToast: Locator;
    readonly salesValidationMessage: Locator;

    /**
     * Inventory - Settings (Operations, Products, Warehouses, Traceability, Logistics)
     */

    readonly inventoryManageOperationsToggleEnablePackages: Locator;
    readonly inventoryManageOperationsAnnualDay: Locator;
    readonly inventoryManageOperationsAnnualMonth: Locator;
    readonly inventoryManageProductsToggleEnableVariants: Locator;
    readonly inventoryManageProductsToggleEnableUom: Locator;
    readonly inventoryManageProductsToggleEnablePackagings: Locator;
    readonly inventoryManageWarehousesToggleEnableLocations: Locator;
    readonly inventoryManageWarehousesToggleEnableMultiSteps: Locator;
    readonly inventoryManageTraceabilityToggleEnableLots: Locator;
    readonly inventoryManageTraceabilityToggleDisplayOnDeliverySlips: Locator;
    readonly inventoryManageLogisticsToggleEnableDropshipping: Locator;
    readonly inventorySettingsSaveButton: Locator;

    /**
     * Inventory - Warehouses, Locations, Operation Types, Routes, Rules
     */

    readonly inventoryWarehouseCreateButton: Locator;
    readonly inventoryWarehouseNameInput: Locator;
    readonly inventoryWarehouseCodeInput: Locator;
    readonly inventoryWarehouseReceptionOneStep: Locator;
    readonly inventoryWarehouseReceptionTwoSteps: Locator;
    readonly inventoryWarehouseReceptionThreeSteps: Locator;
    readonly inventoryWarehouseDeliveryOneStep: Locator;
    readonly inventoryWarehouseDeliveryTwoSteps: Locator;
    readonly inventoryWarehouseDeliveryThreeSteps: Locator;
    readonly inventoryWarehouseSaveButton: Locator;
    readonly inventoryWarehouseEditSaveButton: Locator;
    readonly inventoryWarehouseTable: Locator;
    readonly inventoryWarehouseRowActions: Locator;
    readonly inventoryWarehouseEditAction: Locator;
    // readonly inventoryOpenWarehouseRow: Locator;
    readonly inventoryWarehouseDeleteAction: Locator;
    readonly inventoryWarehouseConfirmDeleteButton: Locator;

    readonly inventoryLocationsTable: Locator;
    readonly inventoryOperationTypesTable: Locator;
    readonly inventoryRoutesTable: Locator;
    readonly inventoryRulesTable: Locator;

    /**
     * Inventory - Products
     */

    readonly inventoryProductCreateButton: Locator;
    readonly inventoryProductNameInput: Locator;
    readonly inventoryProductPriceInput: Locator;
    readonly inventoryProductSaveButton: Locator;
    readonly inventoryProductTable: Locator;
    readonly inventoryProductRowActions: Locator;
    readonly inventoryProductEditAction: Locator;
    readonly inventoryProductDeleteAction: Locator;
    readonly inventoryProductIsStorableToggle: Locator;

    /**
     * Inventory - Operations (Receipts, Deliveries, Internals)
     */

    readonly inventoryOperationCreateButton: Locator;
    readonly inventoryOperationPartnerSelect: Locator;
    readonly inventoryOperationTypeSelect: Locator;
    readonly inventoryOperationSourceLocationSelect: Locator;
    readonly inventoryOperationDestinationLocationSelect: Locator;
    readonly inventoryOperationAddMoveButton: Locator;
    readonly inventoryOperationMoveProductSelect: Locator;
    readonly inventoryOperationMoveDemandInput: Locator;
    readonly inventoryOperationMoveQuantityInput: Locator;
    readonly inventoryOperationSaveButton: Locator;
    readonly inventoryOperationEditSaveButton: Locator;
    readonly inventoryOperationConfirmButton: Locator;
    readonly inventoryOperationMarkAsTodoButton: Locator;
    readonly inventoryOperationCheckAvailabilityButton: Locator;
    readonly inventoryOperationValidateButton: Locator;
    readonly inventoryOperationNoBackorderButton: Locator;
    readonly inventoryOperationStateBadge: Locator;
    readonly inventoryOperationTable: Locator;
    readonly inventoryOperationRowActions: Locator;
    readonly inventoryOperationEditAction: Locator;
    readonly inventoryOperationDeleteAction: Locator;

    /**
     * Inventory - Generic UI helpers
     */

    readonly inventorySearchInput: Locator;
    readonly inventorySelectSearchInput: Locator;
    readonly inventorySelectOption: Locator;
    readonly inventorySuccessToast: Locator;
    readonly inventoryErrorToast: Locator;
    readonly inventoryValidationMessage: Locator;
    readonly inventoryConfirmDialogButton: Locator;

    constructor(page: Page) {
        this.page = page;

        /**
         *  Plugin Install/Uninstall  
         */

        this.pluginSyncButton = page.locator('text=Sync Available Plugins');
        this.pluginthreeDot = page.locator('button[title="Actions"]');
        this.pluginName = page.locator('.fi-size-lg.fi-font-semibold.fi-ta-text-item.fi-ta-text.fi-inline');
        this.pluginInstallButton = page.locator('button.fi-color.fi-color-success.fi-text-color-700');
        this.pluginUninstallButton = page.locator('button.fi-color.fi-color-danger.fi-dropdown-list-item');
        this.pluginConfirmButton = page.locator('span[x-show="! isProcessing"]');
        this.pluginSearchInput = page.locator('.fi-input.fi-input-has-inline-prefix').nth(1);
        this.pluginSuccessMessage = page.locator('h3.fi-no-notification-title');
        this.pluginErrorMessage = page.locator('.fi-toast-message-error');

        /**
         * Companies
         */

        this.allCompaniesCount = page.locator('span.fi-badge-label-ctn').nth(0);
        this.companiesMenuLink = page.getByRole("link", { name: /companies/i });
        this.companiesTable = page.locator("table");
        this.companiesCreateButton = page.locator("a,button").filter({ hasText: /new company|create company|add company|create/i }).first();
        this.companiesNameInput = page.locator('input[id="form.name"]').first();
        this.companiesEmailInput = page.locator('input[id="form.email"]').first();
        this.companiesPhoneInput = page.locator('input[id="form.phone"]').first();
        this.companiesStatusToggleOn = page.locator('button[aria-checked="true"]');
        this.companiesStatusToggleOff = page.locator('button[aria-checked="false"]');
        this.companiesSaveButton = page.locator('button[type="submit"]').nth(1);
        this.companiesSearchInput = page.locator('.fi-input.fi-input-has-inline-prefix').nth(1);
        this.companiesRowActionsButton = page.locator('div.fi-ta-text-item').nth(0);
        this.companiesEditButton = page.locator("a.fi-ac-btn-action");
        this.companiesDeleteButton = page.locator("button.fi-ac-btn-action");
        this.selectAllCompaniesButton = page.locator('input[aria-label="Select/deselect all items for bulk actions."]');
        this.bulkActionsButton = page.locator('button.fi-ac-btn-group').nth(1);
        this.forceDeleteButton = page.locator('span.fi-dropdown-list-item-label').nth(4);
        this.companiesConfirmDeleteButton = page.locator("button[x-data='filamentFormButton']");
        this.companiesStatusToggle = page.locator('button[role="switch"], input[type="checkbox"]').first();
        this.companiesSuccessToast = page.locator("h3.fi-no-notification-title, .fi-toast-message-success").first();
        this.companiesErrorToast = page.locator(".fi-toast-message-error, .fi-input-wrp-error").first();
        this.companiesFeildValidationMessage = page.locator(".fi-fo-field-wrp-error-message", { hasText: /Company name already exists. Please use a unique name./ });
        this.companiesValidationMessage = page.locator(".fi-fo-field-wrp-error-message, .text-danger, .invalid-feedback");

        /**
         * Users
         */

        this.usersMenuLink = page.getByRole("link", { name: /users/i });
        this.allUsersCount = page.locator('span.fi-badge-label-ctn').nth(0);
        this.usersTable = page.locator("table");
        this.usersCreateButton = page.locator("a,button").filter({ hasText: /new user|create user|add user|create/i }).first();
        this.usersInviteButton = page.locator("a,button").filter({ hasText: /invite user|user invitation|invite/i }).first();
        this.usersNameInput = page.locator('input[id="form.name"]');
        this.usersEmailInput = page.locator('input[id="form.email"]');
        this.usersPasswordInput = page.locator('input[id="form.password"]');
        this.usersPasswordConfirmationInput = page.locator('input[id="form.password_confirmation"]');
        this.usersRoleSelect = page.locator('div.fi-select-input-value-ctn').nth(0);
        this.usersCompanySelect = page.locator('div.fi-select-input-value-ctn').nth(5);
        this.usersCompanySearchInput = page.locator('input[type="search"], input[placeholder*="Search"], input[placeholder*="search"]');
        this.usersRoleOption = page.locator('[role="option"], .fi-select-option, li').filter({ hasText: /./ });
        this.usersCompanyOption = page.locator('[role="option"], .fi-select-option, li').filter({ hasText: /./ });
        this.usersSaveButton = page.locator('button[x-data="filamentFormButton"]');
        this.usersSearchInput = page.locator('.fi-input.fi-input-has-inline-prefix').nth(1);
        this.usersRowActionsButton = page.locator('div.fi-ta-text-item').nth(0);
        this.usersEditButton = page.locator("a.fi-ac-btn-action").nth(0);
        this.usersDeleteButton = page.locator("button.fi-ac-btn-action");
        this.usersConfirmDeleteButton = page.getByRole('dialog').getByRole('button', { name: 'Delete' });
        this.selectAllUsersButton = page.locator('input[aria-label="Select/deselect all items for bulk actions."]');
        this.usersBulkActionsButton = page.locator('button.fi-ac-btn-group').nth(1);
        this.usersForceDeleteButton = page.locator('span.fi-dropdown-list-item-label').nth(3);
        this.usersStatusToggle = page.locator('button[role="switch"], input[type="checkbox"]').first();
        this.usersCreateStatusToggle = page.locator('button.fi-fo-toggle');
        this.usersResetPasswordButton = page.locator("button,a").filter({ hasText: /Change Password/i }).first();
        this.usersChangePasswordInput = page.getByRole('textbox', { name: 'New Password*' });
        this.usersChangePasswordConfirmationInput = page.getByRole('textbox', { name: 'Confirm New Password' });
        this.usersChangePasswordSaveButton = page.getByRole('button', { name: 'Submit' });
        this.userMenuButton = page.locator('button[aria-label="User menu"]');
        this.logoutButton = page.getByRole('textbox', { name: 'Confirm New Password' });
        this.usersSuccessToast = page.locator("h3.fi-no-notification-title, .fi-toast-message-success").first();
        this.usersErrorToast = page.locator(".fi-toast-message-error, .fi-input-wrp-error").first();
        this.userFeildValidationMessage = page.locator(".fi-fo-field-wrp-error-message", { hasText: /The email has already been taken./ });
        this.usersValidationMessage = page.locator(".fi-fo-field-wrp-error-message, .text-danger, .invalid-feedback");
        this.manageUsersEnableResetCard = page
            .locator("div,section,li,fieldset")
            .filter({ hasText: /Enable Reset Password|Allow users to reset their password/i })
            .first();
        this.manageUsersEnableResetToggle = page.getByRole("switch", { name: /Enable Reset Password/i });
        this.manageUsersEnableInvitationToggle = page.getByRole("switch", { name: /Enable User Invitation/i });
        this.settingsSaveButton = page.getByRole("button", { name: /Save changes|save|update|submit/i }).first();

        /**
         * Sales - Customers, Products, Quotations
         */

        this.salesCustomersTable = page.locator("div.fi-ta-content-grid, div.fi-ta-empty-state, table");
        this.salesCustomerNewCreateButton = page.locator("a,button").filter({ hasText: /new customer|create customer|add customer|create/i }).first();
        this.salesCustomerNameInput = page.locator('input[id="form.name"]').first();
        this.salesCustomerEmailInput = page.locator('input[id="form.email"]').first();
        this.salesCustomerCreateButton = page.locator('button[id="key-bindings-1"]').first();
        this.salesCustomerSaveButton = page.locator('button[id="key-bindings-2"]').first();
        this.salesCustomerDeleteButton = page.locator('button[id="key-bindings-1"]').first();
        this.salesCustomerSearchInput = page.locator('.fi-input.fi-input-has-inline-prefix').nth(1);
        this.salesCustomerEditButton = page.getByRole('link', { name: 'Edit' }).first();

        this.salesProductsTable = page.locator("table, div.fi-ta-empty-state");
        this.salesProductNewCreateButton = page.locator("a,button").filter({ hasText: /new product|create product|add product|create/i }).first();
        this.salesProductNameInput = page.locator('input[id="form.name"]').first();
        this.salesProductCategorySelect = page.locator('input[id="form.category_id"], [role="combobox"][aria-label*="Category"], [role="combobox"][aria-labelledby*="Category"]').first();
        this.salesProductPriceInput = page.locator('input[id="form.price"]').first();
        this.salesProductUomSelect = page.locator('input[id="form.uom_id"], [role="combobox"][aria-label*="UOM"], [role="combobox"][aria-labelledby*="UOM"]').first();
        this.salesProductCreateButton = page.locator('button[id="key-bindings-1"]').first();
        this.salesProductEditButton = page.getByRole('link', { name: 'Edit' });
        this.salesProductSaveButton = page.locator('button[id="key-bindings-2"]').first();
        this.salesProductDeleteButton = page.getByRole('button', { name: 'Delete' });

        this.salesQuotationCreateButton = page.locator("a,button").filter({ hasText: /new quotation|create quotation|add quotation|create/i }).first();
        this.salesQuotationEditButton = page.getByRole('link', { name: 'Edit' }).first();
        this.salesQuotationCustomerSelect = page.locator('[wire\\:key$="form.partner_id"] button.fi-select-input-btn').first();
        this.salesQuotationPaymentTermSelect = page.locator('[wire\\:key$="form.payment_term_id"] button.fi-select-input-btn').first();
        this.salesQuotationAddProductButton = page.getByRole("button", { name: /Add Product/i }).first();
        this.salesQuotationProductSelectInput = page.locator('[wire\\:key*=".form.products."][wire\\:key*=".product_id."] button.fi-select-input-btn');
        this.salesQuotationQuantityInput = page.locator('input[id^="form.products."][id$=".product_qty"]');
        this.salesQuotationDeleteButton = page.getByRole('button', { name: 'Delete' }).first();
        this.salesQuotationSaveButton = page.getByRole('button', { name: /^(Create|Save changes|Submit)$/i }).first();
        this.salesQuotationConfirmButton = page.getByRole("button", { name: /Confirm/i }).first();
        this.salesQuotationSendButton = page.getByRole("button", { name: /Send by Email|Send/i }).first();
        this.salesQuotationSendSubmitButton = page.getByRole("dialog").getByRole("button", { name: /Send|Submit/i }).first(); 
        this.salesQuotationSentRadio = page.getByRole("radio", { name: /Quotation Sent/i });
        this.salesQuotationCreateInvoiceButton = page.getByRole("button", { name: /Create Invoice/i }).first();
        this.salesQuotationInvoiceSubmitButton = page.getByRole("dialog").getByRole("button", { name: /Create Invoice/i }).first();
        this.salesQuotationDeliveriesTable = page.locator("table, div.fi-ta-empty-state");
        this.salesQuotationDeliveryEditButton = page.getByRole('table').getByRole('link', { name: 'Edit' });
        this.salesDeliveryValidateButton = page.getByRole("button", { name: /Validate/i }).first();
        this.salesDeliveryNoBackorderButton = page.getByRole("button", { name: /No Backorder/i }).first();
        this.salesInvoicesTable = page.locator("table, div.fi-ta-empty-state");

        this.salesSearchInput = page.locator(".fi-input.fi-input-has-inline-prefix").nth(1);
        this.salesRowActionsButton = page.getByRole('button', { name: 'Actions' });
        this.salesEditAction = page.getByRole("menuitem", { name: /Edit/i }).first();
        this.salesDeleteAction = page.getByRole("menuitem", { name: /Delete/i }).first();
        this.salesConfirmDeleteButton = page.getByRole("dialog").getByRole("button", { name: /Delete/i }).first();

        this.salesSelectSearchInput = page.locator('.fi-dropdown-panel[role="listbox"]:visible input.fi-input[aria-label="Search"]').last();
        this.salesSelectOption = page.locator('.fi-dropdown-panel[role="listbox"]:visible [role="option"]');
        this.salesSuccessToast = page.locator("h3.fi-no-notification-title, .fi-toast-message-success").first();
        this.salesValidationMessage = page.locator(".fi-fo-field-wrp-error-message, .text-danger, .invalid-feedback");

        /**
         * Inventory - Settings (Operations, Products, Warehouses, Traceability, Logistics)
         */

        this.inventoryManageOperationsToggleEnablePackages = page.getByRole("switch", { name: /Storage Locations|Packages|Enable Packages/i }).first();
        this.inventoryManageOperationsAnnualDay = page.locator('input[id="form.annual_inventory_day"]');
        this.inventoryManageOperationsAnnualMonth = page.locator('[wire\\:key$="form.annual_inventory_month"] button.fi-select-input-btn').first();
        this.inventoryManageProductsToggleEnableVariants = page.getByRole("switch", { name: /Variants/i }).first();
        this.inventoryManageProductsToggleEnableUom = page.getByRole("switch", { name: /Units of Measure|UoM|UOM/i }).first();
        this.inventoryManageProductsToggleEnablePackagings = page.getByRole("switch", { name: /Packagings/i }).first();
        this.inventoryManageWarehousesToggleEnableLocations = page.getByRole("switch", { name: /Storage Locations|Locations/i }).first();
        this.inventoryManageWarehousesToggleEnableMultiSteps = page.getByRole("switch", { name: /Multi-Step Routes|Multi Step|Multi-step/i }).first();
        this.inventoryManageTraceabilityToggleEnableLots = page.getByRole("switch", { name: /Lots & Serial Numbers|Lots|Serial/i }).first();
        this.inventoryManageTraceabilityToggleDisplayOnDeliverySlips = page.getByRole("switch", { name: /Display Lots & Serial Numbers on Delivery Slips|Delivery Slips/i }).first();
        this.inventoryManageLogisticsToggleEnableDropshipping = page.getByRole("switch", { name: /Dropshipping/i }).first();
        this.inventorySettingsSaveButton = page.getByRole("button", { name: /Save changes|save|update|submit/i }).first();

        /**
         * Inventory - Warehouses, Locations, Operation Types, Routes, Rules
         */

        this.inventoryWarehouseCreateButton = page.locator("a,button").filter({ hasText: /new warehouse|create warehouse|add warehouse|create/i }).first();
        this.inventoryWarehouseNameInput = page.locator('input[id="form.name"]').first();
        this.inventoryWarehouseCodeInput = page.locator('input[id="form.code"]').first();
        this.inventoryWarehouseReceptionOneStep = page.getByRole("radio", { name: /One step/i }).first();
        this.inventoryWarehouseReceptionTwoSteps = page.getByRole("radio", { name: /Two steps/i }).first();
        this.inventoryWarehouseReceptionThreeSteps = page.getByRole("radio", { name: /Three steps/i }).first();
        this.inventoryWarehouseDeliveryOneStep = page.getByRole("radio", { name: /One step/i }).nth(1);
        this.inventoryWarehouseDeliveryTwoSteps = page.getByRole("radio", { name: /Two steps/i }).nth(1);
        this.inventoryWarehouseDeliveryThreeSteps = page.getByRole("radio", { name: /Three steps/i }).nth(1);
        this.inventoryWarehouseSaveButton = page.locator('button[id="key-bindings-1"]').first();
        // this.inventoryOpenWarehouseRow = page.
        this.inventoryWarehouseEditSaveButton = page.locator('button[id="key-bindings-2"]').first();
        this.inventoryWarehouseTable = page.locator("table, div.fi-ta-empty-state");
        this.inventoryWarehouseRowActions = page.getByRole("button", { name: "Actions" }).first();
        this.inventoryWarehouseEditAction = page.locator("a.fi-ac-link-action").nth(1); 
        this.inventoryWarehouseDeleteAction = page.getByRole("button", { name: /Delete/i }).first();
        this.inventoryWarehouseConfirmDeleteButton = page.getByRole("dialog").getByRole("button", { name: /Delete/i }).first();

        this.inventoryLocationsTable = page.locator("table, div.fi-ta-empty-state");
        this.inventoryOperationTypesTable = page.locator("table, div.fi-ta-empty-state");
        this.inventoryRoutesTable = page.locator("table, div.fi-ta-empty-state");
        this.inventoryRulesTable = page.locator("table, div.fi-ta-empty-state");

        /**
         * Inventory - Products
         */

        this.inventoryProductCreateButton = page.locator("a,button").filter({ hasText: /new product|create product|add product|create/i }).first();
        this.inventoryProductNameInput = page.locator('input[id="form.name"]').first();
        this.inventoryProductPriceInput = page.locator('input[id="form.price"]').first();
        this.inventoryProductSaveButton = page.locator('button[id="key-bindings-1"]').first();
        this.inventoryProductTable = page.locator("table, div.fi-ta-empty-state");
        this.inventoryProductRowActions = page.getByRole("button", { name: "Actions" }).first();
        this.inventoryProductEditAction = page.getByRole("link", { name: /Edit/i }).first();
        this.inventoryProductDeleteAction = page.getByRole("button", { name: /Delete/i }).first();
        this.inventoryProductIsStorableToggle = page.getByRole("switch", { name: /Track Inventory|Storable/i }).first();

        /**
         * Inventory - Operations (Receipts, Deliveries, Internals)
         */

        this.inventoryOperationCreateButton = page.locator("a,button").filter({ hasText: /new receipt|new delivery|new internal|new transfer|create receipt|create delivery|create internal|create transfer|create/i }).first();
        this.inventoryOperationPartnerSelect = page.locator('[wire\\:key$="form.partner_id"] button.fi-select-input-btn').first();
        this.inventoryOperationTypeSelect = page.locator('[wire\\:key$="form.operation_type_id"] button.fi-select-input-btn').first();
        this.inventoryOperationSourceLocationSelect = page.locator('[wire\\:key$="form.source_location_id"] button.fi-select-input-btn').first();
        this.inventoryOperationDestinationLocationSelect = page.locator('[wire\\:key$="form.destination_location_id"] button.fi-select-input-btn').first();
        this.inventoryOperationAddMoveButton = page.getByRole("button", { name: /Add a line|Add Move|Add Item|Add to Cart|Add product|Add/i }).first();
        this.inventoryOperationMoveProductSelect = page.locator('[wire\\:key*=".form.moves."][wire\\:key*=".product_id."] button.fi-select-input-btn');
        this.inventoryOperationMoveDemandInput = page.locator('input[id^="form.moves."][id$=".product_uom_qty"]');
        this.inventoryOperationMoveQuantityInput = page.locator('input[id^="form.moves."][id$=".quantity"]');
        this.inventoryOperationSaveButton = page.locator('button[id="key-bindings-1"]').first();
        this.inventoryOperationEditSaveButton = page.locator('button[id="key-bindings-2"]').first();
        this.inventoryOperationConfirmButton = page.getByRole("button", { name: /^Confirm$/i }).first();
        this.inventoryOperationMarkAsTodoButton = page.getByRole("button", { name: /Mark as Todo/i }).first();
        this.inventoryOperationCheckAvailabilityButton = page.getByRole("button", { name: /Check Availability/i }).first();
        this.inventoryOperationValidateButton = page.getByRole("button", { name: /^Validate$/i }).first();
        this.inventoryOperationNoBackorderButton = page.getByRole("button", { name: /No Backorder/i }).first();
        this.inventoryOperationStateBadge = page.locator('[wire\\:key$="form.state"], .fi-progress-stepper').first();
        this.inventoryOperationTable = page.locator("table, div.fi-ta-empty-state");
        this.inventoryOperationRowActions = page.getByRole("button", { name: "Actions" }).first();
        this.inventoryOperationEditAction = page.getByRole("link", { name: /Edit/i }).first();
        this.inventoryOperationDeleteAction = page.getByRole("button", { name: /Delete/i }).first();

        /**
         * Inventory - Generic UI helpers
         */

        this.inventorySearchInput = page.locator(".fi-input.fi-input-has-inline-prefix").nth(1);
        this.inventorySelectSearchInput = page.locator('.fi-dropdown-panel[role="listbox"]:visible input.fi-input[aria-label="Search"]').last();
        this.inventorySelectOption = page.locator('.fi-dropdown-panel[role="listbox"]:visible [role="option"]');
        this.inventorySuccessToast = page.locator("h3.fi-no-notification-title, .fi-toast-message-success").first();
        this.inventoryErrorToast = page.locator(".fi-toast-message-error, .fi-input-wrp-error").first();
        this.inventoryValidationMessage = page.locator(".fi-fo-field-wrp-error-message, .text-danger, .invalid-feedback");
        this.inventoryConfirmDialogButton = page.getByRole("dialog").getByRole("button", { name: /Confirm|Delete|Yes/i }).first();
    }
}
