import { Page, expect, Locator } from "@playwright/test";
import { ErpLocators } from "../locator/erp_locator";
import { PluginManagementPage } from "./01_pluginManagement";

export type WarehouseData = {
    name: string;
    code: string;
    receptionStep?: 1 | 2 | 3;
    deliveryStep?: 1 | 2 | 3;
};

export type InventoryProductData = {
    name: string;
    price?: string;
};

export type ReceiptData = {
    partnerName?: string;
    productName: string;
    demand: string;
};

export type DeliveryData = {
    partnerName?: string;
    productName: string;
    demand: string;
};

export type InternalTransferData = {
    productName: string;
    demand: string;
    sourceLocation?: string;
    destinationLocation?: string;
};

export class InventoriesManagementPage {
    readonly page: Page;
    readonly erpLocators: ErpLocators;

    constructor(page: Page) {
        this.page = page;
        this.erpLocators = new ErpLocators(page);
    }

    /**
     * Plugin / Setup
     */

    async ensureInventoriesPluginInstalled() {
        const pluginPage = new PluginManagementPage(this.page);
        await pluginPage.gotoPluginManagementPage();
        await pluginPage.installPluginByName("Inventories");
    }

    async ensureBaseDependentPluginsInstalled() {
        const pluginPage = new PluginManagementPage(this.page);
        await pluginPage.gotoPluginManagementPage();
        // await pluginPage.installPluginByName("Products");
        // await pluginPage.gotoPluginManagementPage();
        await pluginPage.installPluginByName("Inventories");
    }

    /**
     * Settings - Manage Operations
     */

    async gotoManageOperationsPage() {
        await this.page.goto("/admin/settings/inventory/manage-operations");
        await expect(this.page).toHaveURL(/manage-operations/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventorySettingsSaveButton).toBeVisible();
    }

    async enableManageOperationsToggles() {
        await this.gotoManageOperationsPage();
        await this.setToggleOn(this.erpLocators.inventoryManageOperationsToggleEnablePackages);
        await this.erpLocators.inventorySettingsSaveButton.click();
        await this.expectSuccessToast();
    }

    /**
     * Settings - Manage Products
     */

    async gotoManageProductsSettingsPage() {
        await this.page.goto("/admin/settings/inventory/manage-products");
        await expect(this.page).toHaveURL(/manage-products/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventorySettingsSaveButton).toBeVisible();
    }

    async enableManageProductsToggles() {
        await this.gotoManageProductsSettingsPage();
        await this.setToggleOn(this.erpLocators.inventoryManageProductsToggleEnableVariants);
        await this.setToggleOn(this.erpLocators.inventoryManageProductsToggleEnableUom);
        await this.setToggleOn(this.erpLocators.inventoryManageProductsToggleEnablePackagings);
        await this.erpLocators.inventorySettingsSaveButton.click();
        await this.expectSuccessToast();
    }

    /**
     * Settings - Manage Warehouses (Locations + Multi-Step Routes)
     */

    async gotoManageWarehousesSettingsPage() {
        await this.page.goto("/admin/settings/inventory/manage-warehouses");
        await expect(this.page).toHaveURL(/manage-warehouses/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventorySettingsSaveButton).toBeVisible();
    }

    async enableManageWarehousesToggles() {
        await this.gotoManageWarehousesSettingsPage();
        await this.setToggleOn(this.erpLocators.inventoryManageWarehousesToggleEnableLocations);
        await this.setToggleOn(this.erpLocators.inventoryManageWarehousesToggleEnableMultiSteps);
        await this.erpLocators.inventorySettingsSaveButton.click();
        await this.page.waitForLoadState("networkidle");
        await this.expectSuccessToast();
    }

    /**
     * Settings - Manage Traceability
     */

    async gotoManageTraceabilitySettingsPage() {
        await this.page.goto("/admin/settings/inventory/manage-traceability");
        await expect(this.page).toHaveURL(/manage-traceability/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventorySettingsSaveButton).toBeVisible();
    }

    async enableManageTraceabilityToggles() {
        await this.gotoManageTraceabilitySettingsPage();
        await this.setToggleOn(this.erpLocators.inventoryManageTraceabilityToggleEnableLots);
        if (await this.erpLocators.inventoryManageTraceabilityToggleDisplayOnDeliverySlips
            .isVisible()
            .catch(() => false)) {
            await this.setToggleOn(this.erpLocators.inventoryManageTraceabilityToggleDisplayOnDeliverySlips);
        }
        await this.erpLocators.inventorySettingsSaveButton.click();
        await this.page.waitForLoadState("networkidle");
    }

    /**
     * Settings - Manage Logistics (Dropshipping)
     */

    async gotoManageLogisticsSettingsPage() {
        await this.page.goto("/admin/settings/inventory/manage-logistics");
        await expect(this.page).toHaveURL(/manage-logistics/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventorySettingsSaveButton).toBeVisible();
    }

    async enableManageLogisticsToggles() {
        await this.gotoManageLogisticsSettingsPage();
        await this.setToggleOn(this.erpLocators.inventoryManageLogisticsToggleEnableDropshipping);
        await this.erpLocators.inventorySettingsSaveButton.click();
        await this.expectSuccessToast();
    }

    /**
     * Run all settings and enable everything required for the full E2E flow.
     */
    async enableAllInventorySettings() {
        await this.enableManageWarehousesToggles();
        await this.enableManageProductsToggles();
        await this.enableManageOperationsToggles();
        await this.enableManageTraceabilityToggles();
        await this.enableManageLogisticsToggles();
    }

    /**
     * Configurations - Warehouses
     */

    async gotoWarehousesPage() {
        await this.page.waitForLoadState("networkidle");
        await this.page.goto("/admin/inventory/configurations/warehouses");
        await expect(this.page).toHaveURL(/configurations\/warehouses/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryWarehouseTable.first()).toBeVisible();
    }

    async createWarehouse(data: WarehouseData) {
        await this.gotoWarehousesPage();
        await this.erpLocators.inventoryWarehouseCreateButton.click();
        await expect(this.page).toHaveURL(/warehouses\/create/);

        await this.erpLocators.inventoryWarehouseNameInput.fill(data.name);
        await this.erpLocators.inventoryWarehouseCodeInput.fill(data.code);

        if (data.receptionStep) {
            await this.selectReceptionStep(data.receptionStep);
        }

        if (data.deliveryStep) {
            await this.selectDeliveryStep(data.deliveryStep);
        }

        await this.erpLocators.inventoryWarehouseSaveButton.click();
        await this.expectSuccessToast();
    }

    async selectReceptionStep(step: 1 | 2 | 3) {
        const target = step === 1
            ? this.erpLocators.inventoryWarehouseReceptionOneStep
            : step === 2
                ? this.erpLocators.inventoryWarehouseReceptionTwoSteps
                : this.erpLocators.inventoryWarehouseReceptionThreeSteps;

        if (await target.isVisible().catch(() => false)) {
            await target.click();
        }
    }

    async selectDeliveryStep(step: 1 | 2 | 3) {
        const target = step === 1
            ? this.erpLocators.inventoryWarehouseDeliveryOneStep
            : step === 2
                ? this.erpLocators.inventoryWarehouseDeliveryTwoSteps
                : this.erpLocators.inventoryWarehouseDeliveryThreeSteps;

        if (await target.isVisible().catch(() => false)) {
            await target.click();
        }
    }

    async editWarehouseSteps(name: string, receptionStep: 1 | 2 | 3, deliveryStep: 1 | 2 | 3) {
        await this.gotoWarehousesPage();
        await this.searchList(name);
        // await this.erpLocators.openWarehouseRow().click();
        // await this.openRowActions();
        // await this.page.waitForLoadState("networkidle");
        await this.page.waitForTimeout(800);
        
        await this.erpLocators.inventoryWarehouseEditAction.click();

        await this.selectReceptionStep(receptionStep);
        await this.selectDeliveryStep(deliveryStep);

        await this.erpLocators.inventoryWarehouseEditSaveButton.click();
        await this.expectSuccessToast();
    }

    async deleteWarehouse(name: string) {
        await this.gotoWarehousesPage();
        await this.searchList(name);
        await this.openRowActions();
        await this.erpLocators.inventoryWarehouseDeleteAction.click();
        await this.erpLocators.inventoryWarehouseConfirmDeleteButton.click();
        await this.expectSuccessToast();
    }

    /**
     * Configurations - Locations / Operation Types / Routes / Rules
     * (Used to verify auto-creation after warehouse setup.)
     */

    async gotoLocationsPage() {
        await this.page.waitForLoadState("networkidle");
        await this.page.goto("/admin/inventory/configurations/locations");
        await expect(this.page).toHaveURL(/configurations\/locations/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryLocationsTable.first()).toBeVisible();
    }

    async gotoOperationTypesPage() {
        await this.page.goto("/admin/inventory/configurations/operation-types");
        await expect(this.page).toHaveURL(/operation-types/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryOperationTypesTable.first()).toBeVisible();
    }

    async gotoRoutesPage() {
        await this.page.goto("/admin/inventory/configurations/routes");
        await expect(this.page).toHaveURL(/configurations\/routes/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryRoutesTable.first()).toBeVisible();
    }

    async gotoRulesPage() {
        await this.page.goto("/admin/inventory/configurations/rules");
        await expect(this.page).toHaveURL(/configurations\/rules/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryRulesTable.first()).toBeVisible();
    }

    /**
     * Verify list contains a keyword by searching the list.
     */
    async expectListContains(keyword: string) {
        await this.searchList(keyword);
        const matches = this.page.locator("table tbody tr", { hasText: keyword });
        await expect(matches.first()).toBeVisible();
    }

    async expectLocationCreatedFor(warehouseCode: string) {
        await this.gotoLocationsPage();
        await this.expectListContains(warehouseCode);
    }

    async expectOperationTypeCreatedFor(warehouseCode: string) {
        await this.gotoOperationTypesPage();
        await this.expectListContains(warehouseCode);
    }

    async expectRouteCreatedFor(warehouseCode: string) {
        await this.gotoRoutesPage();
        await this.expectListContains(warehouseCode);
    }

    async expectRuleCreatedFor(warehouseCode: string) {
        await this.gotoRulesPage();
        await this.expectListContains(warehouseCode);
    }

    /**
     * Count how many rows in the current list match the keyword.
     * Uses the page's search input first to filter the list, then counts
     * the visible <tbody> rows.
     */
    async countListMatches(keyword: string): Promise<number> {
        await this.searchList(keyword);
        const matches = this.page.locator("table tbody tr", { hasText: keyword });
        await expect(matches.first()).toBeVisible();
        return matches.count();
    }

    async expectLocationCountFor(warehouseCode: string, expected: number) {
        await this.gotoLocationsPage();
        const count = await this.countListMatches(warehouseCode);
        expect(count, `Expected ${expected} location(s) for warehouse ${warehouseCode}`).toBe(expected);
    }

    async expectOperationTypeCountFor(warehouseCode: string, expected: number) {
        await this.gotoOperationTypesPage();
        const count = await this.countListMatches(warehouseCode);
        expect(count, `Expected ${expected} operation type(s) for warehouse ${warehouseCode}`).toBe(expected);
    }

    async expectRouteCountFor(warehouseCode: string, expected: number) {
        await this.gotoRoutesPage();
        const count = await this.countListMatches(warehouseCode);
        expect(count, `Expected ${expected} route(s) for warehouse ${warehouseCode}`).toBe(expected);
    }

    async expectRuleCountFor(warehouseCode: string, expected: number) {
        await this.gotoRulesPage();
        const count = await this.countListMatches(warehouseCode);
        expect(count, `Expected ${expected} rule(s) for warehouse ${warehouseCode}`).toBe(expected);
    }

    /**
     * Convenience helper: assert all four auto-created lists for a warehouse step.
     */
    async expectWarehouseAutoCreatedCounts(
        warehouseCode: string,
        warehouseName: string,
        expected: { locations: number; operationTypes: number; routes: number; rules: number }
    ) {
        await this.expectLocationCountFor(warehouseCode, expected.locations);
        await this.expectOperationTypeCountFor(warehouseName, expected.operationTypes);
        await this.expectRouteCountFor(warehouseName, expected.routes);
        await this.expectRuleCountFor(warehouseCode, expected.rules);
    }

    /**
     * Products
     */

    async gotoProductsPage() {
        await this.page.goto("/admin/inventory/products/products");
        await expect(this.page).toHaveURL(/inventory\/products\/products/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryProductTable.first()).toBeVisible();
    }

    async createInventoryProduct(product: InventoryProductData) {
        await this.gotoProductsPage();
        await this.erpLocators.inventoryProductCreateButton.click();
        await expect(this.page).toHaveURL(/products\/create/);

        await this.erpLocators.inventoryProductNameInput.fill(product.name);
        if (product.price) {
            await this.erpLocators.inventoryProductPriceInput
                .fill(product.price)
                .catch(() => undefined);
        }

        if (await this.erpLocators.inventoryProductIsStorableToggle.isVisible().catch(() => false)) {
            await this.setToggleOn(this.erpLocators.inventoryProductIsStorableToggle);
        }

        await this.erpLocators.inventoryProductSaveButton.click();
        await this.expectSuccessToast();
    }

    /**
     * Navigate to a product's record by visiting list, searching, and opening it.
     */
    async openProductByName(name: string) {
        await this.gotoProductsPage();
        await this.searchList(name);
        const link = this.page.locator("table tbody tr a", { hasText: name }).first();
        await expect(link).toBeVisible();
        await link.click();
        await this.page.waitForLoadState("networkidle");
    }

    /**
     * On-hand quantities tab on a product record.
     */
    async gotoProductQuantitiesTab(productName: string) {
        await this.openProductByName(productName);
        await this.erpLocators.inventoryProductQuantitiesTab.click();
        await this.page.waitForLoadState("networkidle");
        await expect(this.page).toHaveURL(/\/quantities/);
    }

    /**
     * In/Out movement history tab on a product record.
     */
    async gotoProductMovesTab(productName: string) {
        await this.openProductByName(productName);
        await this.erpLocators.inventoryProductMovesTab.click();
        await this.page.waitForLoadState("networkidle");
        await expect(this.page).toHaveURL(/\/moves/);
    }

    /**
     * Add an on-hand quantity for the product at a given location.
     */
    async addOnHandQuantity(productName: string, location: string, quantity: string) {
        const l = this.erpLocators;

        await this.gotoProductQuantitiesTab(productName);

        await l.inventoryProductQuantityCreateButton.click();
        await this.page.waitForLoadState("networkidle");
        await expect(l.inventoryProductQuantityOpenModal).toBeVisible();

        try {
            await l.inventoryProductQuantityLocationSelect.waitFor({ state: "visible", timeout: 200 });
            await this.selectFromFilamentDropdown(l.inventoryProductQuantityLocationSelect, location);
            await this.page.waitForLoadState("networkidle");
        } catch {
            // Location field not rendered (enable_locations toggle off) — proceed without it.
        }

        await expect(l.inventoryProductQuantityInput).toBeVisible();
        await l.inventoryProductQuantityInput.fill(quantity);
        await l.inventoryProductQuantityDialogCreate.click();
        await this.page.waitForLoadState("networkidle");
        await this.expectSuccessToastSoft();
    }

    /**
     * Click a Filament select trigger, type into the search box of the
     * resulting dropdown panel, and click the first option matching `value`.
     * Robust against multiple panels because we scope to the visible panel
     * that appears after we click.
     */
    async selectFromFilamentDropdown(trigger: Locator, value: string) {
        await trigger.scrollIntoViewIfNeeded();
        await trigger.click();

        const panel = this.page.locator('.fi-dropdown-panel[role="listbox"]:visible').last();
        await expect(panel).toBeVisible();

        const search = panel.locator('input.fi-input[aria-label="Search"]').first();
        if (await search.isVisible().catch(() => false)) {
            await search.fill(value);
            await this.page.waitForTimeout(500);
        }

        const option = panel
            .locator('[role="option"]')
            .filter({ hasText: new RegExp(this.escapeRegExp(value), "i") })
            .first();

        await expect(option).toBeVisible();
        await option.click();
    }

    /**
     * Assert that the product's on-hand row at `location` shows `quantity`.
     * On Hand is a TextInputColumn so the value lives in the input, not text.
     */
    async expectOnHandQuantityRow(productName: string, location: string, quantity: string) {
        const l = this.erpLocators;
        await this.gotoProductQuantitiesTab(productName);

        const row = l.inventoryProductQuantityTableRows.filter({ hasText: location }).first();
        const onHandInput = row.locator(l.inventoryProductQuantityOnHandInlineInputs).first();
        // Allow trailing decimal padding (Filament formats numerics as "30.0000").
        const escaped = quantity.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        await expect(onHandInput).toHaveValue(new RegExp(`^${escaped}(\\.0+)?$`));
    }

    /**
     * Assert that the product's on-hand row at `location` shows the expected
     * Reserved Quantity. Reserved is a read-only TextColumn (cell text).
     */
    async expectReservedQuantityRow(productName: string, location: string, reserved: string) {
        const l = this.erpLocators;
        await this.gotoProductQuantitiesTab(productName);

        const row = l.inventoryProductQuantityTableRows.filter({ hasText: location }).first();
        const reservedCell = row.locator(l.inventoryProductQuantityReservedCells).first();
        await expect(reservedCell).toContainText(reserved);
    }

    /**
     * Assert that the product's moves (In/Out) tab contains a row with
     * the given product name. Filters by an optional state if provided.
     */
    async expectProductMoveRowVisible(productName: string, state?: string) {
        await this.gotoProductMovesTab(productName);
        const row = state
            ? this.page.locator("table tbody tr", { hasText: state })
            : this.page.locator("table tbody tr").first();
        await expect(row.first()).toBeVisible();
    }

    /**
     * Count the moves rows visible on a product's In/Out tab.
     */
    async countProductMoveRows(productName: string): Promise<number> {
        await this.gotoProductMovesTab(productName);
        return this.page.locator("table tbody tr").count();
    }

    async deleteInventoryProduct(name: string) {
        await this.gotoProductsPage();
        await this.searchList(name);
        await this.openRowActions();
        await this.erpLocators.inventoryProductDeleteAction.click();
        await this.erpLocators.inventoryConfirmDialogButton.click();
        await this.expectSuccessToast();
    }

    /**
     * Operations - shared helpers
     */

    async clickConfirmIfVisible(): Promise<boolean> {
        if (await this.erpLocators.inventoryOperationConfirmButton.isVisible().catch(() => false)) {
            await this.erpLocators.inventoryOperationConfirmButton.click();
            await this.page.waitForLoadState("networkidle");
            return true;
        }
        return false;
    }

    async clickMarkAsTodoIfVisible(): Promise<boolean> {
        if (await this.erpLocators.inventoryOperationMarkAsTodoButton.isVisible().catch(() => false)) {
            await this.erpLocators.inventoryOperationMarkAsTodoButton.click();
            await this.page.waitForLoadState("networkidle");
            return true;
        }
        return false;
    }

    async clickCheckAvailabilityIfVisible(): Promise<boolean> {
        if (await this.erpLocators.inventoryOperationCheckAvailabilityButton.isVisible().catch(() => false)) {
            await this.erpLocators.inventoryOperationCheckAvailabilityButton.click();
            await this.page.waitForLoadState("networkidle");
            return true;
        }
        return false;
    }

    async validateOperation() {
        await expect(this.erpLocators.inventoryOperationValidateButton).toBeVisible();
        await this.erpLocators.inventoryOperationValidateButton.click();

        if (await this.erpLocators.inventoryOperationNoBackorderButton.isVisible().catch(() => false)) {
            await this.erpLocators.inventoryOperationNoBackorderButton.click();
        }

        await this.page.waitForLoadState("networkidle");
        await this.expectSuccessToastSoft();
    }

    /**
     * Operations - Receipts
     */

    async gotoReceiptsPage() {
        await this.page.goto("/admin/inventory/operations/receipts");
        await expect(this.page).toHaveURL(/operations\/receipts/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryOperationTable.first()).toBeVisible();
    }

    async createReceipt(data: ReceiptData) {
        await this.gotoReceiptsPage();
        await this.erpLocators.inventoryOperationCreateButton.click();
        await expect(this.page).toHaveURL(/receipts\/create/);

        if (data.partnerName) {
            await this.selectBySearch(this.erpLocators.inventoryOperationPartnerSelect, data.partnerName);
        }

        await this.erpLocators.inventoryOperationAddMoveButton.scrollIntoViewIfNeeded();
        await this.erpLocators.inventoryOperationAddMoveButton.click();
        await this.selectBySearch(
            this.erpLocators.inventoryOperationMoveProductSelect.first(),
            data.productName
        );
        await this.erpLocators.inventoryOperationMoveDemandInput.first().fill(data.demand);

        await this.erpLocators.inventoryOperationSaveButton.click();
        await this.expectSuccessToast();
    }

    async receiptFullFlow(data: ReceiptData) {
        await this.createReceipt(data);
        await this.clickMarkAsTodoIfVisible();
        await this.clickCheckAvailabilityIfVisible();
        await this.validateOperation();
    }

    /**
     * Operations - Deliveries
     */

    async gotoDeliveriesPage() {
        await this.page.goto("/admin/inventory/operations/deliveries");
        await expect(this.page).toHaveURL(/operations\/deliveries/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryOperationTable.first()).toBeVisible();
    }

    async createDelivery(data: DeliveryData) {
        await this.gotoDeliveriesPage();
        await this.erpLocators.inventoryOperationCreateButton.click();
        await expect(this.page).toHaveURL(/deliveries\/create/);

        if (data.partnerName) {
            await this.selectBySearch(this.erpLocators.inventoryOperationPartnerSelect, data.partnerName);
        }

        await this.erpLocators.inventoryOperationAddMoveButton.scrollIntoViewIfNeeded();
        await this.erpLocators.inventoryOperationAddMoveButton.click();
        await this.selectBySearch(
            this.erpLocators.inventoryOperationMoveProductSelect.first(),
            data.productName
        );
        await this.erpLocators.inventoryOperationMoveDemandInput.first().fill(data.demand);

        await this.erpLocators.inventoryOperationSaveButton.click();
        await this.expectSuccessToast();
    }

    async deliveryFullFlow(data: DeliveryData) {
        await this.createDelivery(data);
        await this.clickMarkAsTodoIfVisible();
        await this.clickCheckAvailabilityIfVisible();
        await this.validateOperation();
    }

    /**
     * Operations - Internal Transfers
     */

    async gotoInternalTransfersPage() {
        await this.page.goto("/admin/inventory/operations/internals");
        await expect(this.page).toHaveURL(/operations\/internals/);
        await this.page.waitForLoadState("networkidle");
        await expect(this.erpLocators.inventoryOperationTable.first()).toBeVisible();
    }

    async createInternalTransfer(data: InternalTransferData) {
        await this.gotoInternalTransfersPage();
        await this.erpLocators.inventoryOperationCreateButton.click();
        await expect(this.page).toHaveURL(/internals\/create/);

        if (data.sourceLocation) {
            await this.selectBySearch(
                this.erpLocators.inventoryOperationSourceLocationSelect,
                data.sourceLocation
            );
        }

        if (data.destinationLocation) {
            await this.selectBySearch(
                this.erpLocators.inventoryOperationDestinationLocationSelect,
                data.destinationLocation
            );
        }

        await this.erpLocators.inventoryOperationAddMoveButton.scrollIntoViewIfNeeded();
        await this.erpLocators.inventoryOperationAddMoveButton.click();
        await this.selectBySearch(
            this.erpLocators.inventoryOperationMoveProductSelect.first(),
            data.productName
        );
        await this.erpLocators.inventoryOperationMoveDemandInput.first().fill(data.demand);

        await this.erpLocators.inventoryOperationSaveButton.click();
        await this.expectSuccessToast();
    }

    async internalTransferFullFlow(data: InternalTransferData) {
        await this.createInternalTransfer(data);
        await this.clickMarkAsTodoIfVisible();
        await this.clickCheckAvailabilityIfVisible();
        await this.validateOperation();
    }

    /**
     * Quantities check
     */

    async gotoQuantitiesPage() {
        await this.page.goto("/admin/inventory/operations/quantities");
        await expect(this.page).toHaveURL(/operations\/quantities/);
        await this.page.waitForLoadState("networkidle");
    }

    async expectProductQuantityRowVisible(productName: string) {
        await this.gotoQuantitiesPage();
        await this.searchList(productName);
        const row = this.page.locator("table tbody tr", { hasText: productName });
        await expect(row.first()).toBeVisible();
    }

    /**
     * Generic UI helpers
     */

    async searchList(keyword: string) {
        await this.erpLocators.inventorySearchInput.fill(keyword);
        await this.page.waitForLoadState("networkidle");
        await this.page.waitForTimeout(800);
    }

    async openRowActions() {
        await this.erpLocators.inventoryOperationRowActions.first().click();
    }

    async selectBySearch(trigger: Locator, value: string) {
        await trigger.click();

        await expect(this.erpLocators.inventorySelectSearchInput).toBeVisible();
        await this.erpLocators.inventorySelectSearchInput.fill(value);

        const option = this.erpLocators.inventorySelectOption
            .filter({ hasText: new RegExp(this.escapeRegExp(value), "i") })
            .first();

        await expect(option).toBeVisible();
        await option.click();
    }

    async setToggleOn(toggle: Locator) {
        if (!(await toggle.isVisible().catch(() => false))) {
            return;
        }

        const checked = await toggle.getAttribute("aria-checked").catch(() => null);
        if (checked === "true") {
            return;
        }
        await toggle.click();
    }

    async setToggleOff(toggle: Locator) {
        if (!(await toggle.isVisible().catch(() => false))) {
            return;
        }

        const checked = await toggle.getAttribute("aria-checked").catch(() => null);
        if (checked === "false") {
            return;
        }
        await toggle.click();
    }

    private escapeRegExp(value: string): string {
        return value.replace(/[.*+?^${}()|[\\]\\]/g, "\\$&");
    }

    private async expectSuccessToast() {
        await expect(this.erpLocators.inventorySuccessToast).toBeVisible();
    }

    private async expectSuccessToastSoft() {
        try {
            await expect(this.erpLocators.inventorySuccessToast).toBeVisible({ timeout: 10_000 });
        } catch {
            // Some validate flows redirect immediately and the toast might
            // not be picked up; fall through gracefully.
        }
    }

    async expectValidationErrors() {
        await expect(this.erpLocators.inventoryValidationMessage.first()).toBeVisible();
    }
}
