import { test } from "../../setup";
import { InventoriesManagementPage } from "../../pages/06_inventoriesManagement";

test.describe("Inventory End-To-End Flow - 3-Step Warehouse, Receipt -> Internal Moves -> Validate", () => {
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
        // Enable everything: locations, multi-step routes, packagings,
        // UoM, traceability, dropshipping. Required for the full 3-step flow.
        await inventoryPage.enableAllInventorySettings();
    });

    test("Three-Step Incoming - Receipt Then Internal Transfers Then Stock Check", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const warehouseName = `WH Flow ${key}`;
        const warehouseCode = `WF${key}`.slice(-5);
        const productName = `E2E Flow Product ${key}`;

        // 1) Create a 3-step incoming + 3-step delivery warehouse.
        await inventoryPage.createWarehouse({
            name: warehouseName,
            code: warehouseCode,
            receptionStep: 3,
            deliveryStep: 3,
        });

        // 2) Verify auto-created locations, operation types, routes, rules.
        await inventoryPage.expectLocationCreatedFor(warehouseName);
        await inventoryPage.expectOperationTypeCreatedFor(warehouseName);
        await inventoryPage.expectRouteCreatedFor(warehouseName);
        await inventoryPage.expectRuleCreatedFor(warehouseName);

        // 3) Create a storable product.
        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "100",
        });

        // 4) Receipt: receive stock at the input location (step 1 of 3).
        await inventoryPage.receiptFullFlow({
            productName,
            demand: "25",
        });

        // 5) For 3-step incoming, the route auto-creates the QC and
        // store internal transfers. Validate them in sequence.
        await inventoryPage.gotoInternalTransfersPage();
        await inventoryPage.searchList(productName);

        // 6) Validate the first auto-created internal transfer (input -> QC).
        await inventoryPage.internalTransferFullFlow({
            productName,
            demand: "25",
        });

        // 7) Validate quantities are reflected on the product quantities page.
        await inventoryPage.expectProductQuantityRowVisible(productName);

        // 8) Issue an outgoing delivery to verify Out movement.
        await inventoryPage.deliveryFullFlow({
            productName,
            demand: "5",
        });

        await inventoryPage.expectProductQuantityRowVisible(productName);
    });
});
