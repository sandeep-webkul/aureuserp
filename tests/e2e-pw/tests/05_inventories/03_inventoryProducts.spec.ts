import { test } from "../../setup";
import { InventoriesManagementPage } from "../../pages/06_inventoriesManagement";

test.describe("Inventory Products - CRUD, Quantities & In/Out Tab", () => {
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
        // Locations + multi-step routes must be on for the location-based
        // on-hand quantity selectors and the moves tab to render.
        await inventoryPage.enableManageWarehousesToggles();
    });

    test("Products Listing - Loads Table", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.gotoProductsPage();
    });

    test("Create Storable Product - Valid Inputs", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        await inventoryPage.createInventoryProduct({
            name: `E2E Inv Product ${key}`,
            price: "150",
        });
    });

    test("Delete Product - Removes Record", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Inv Delete ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "75",
        });

        await inventoryPage.deleteInventoryProduct(productName);
    });

    test("Product Quantities Tab - Loads For Storable Product", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Qty Tab ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "10",
        });

        await inventoryPage.gotoProductQuantitiesTab(productName);
    });

    test("Product In/Out Moves Tab - Loads For Storable Product", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Moves Tab ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "15",
        });

        await inventoryPage.gotoProductMovesTab(productName);
    });

    test("Add On-Hand Quantity At Default Stock Location", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E On-Hand ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "20",
        });

        await inventoryPage.addOnHandQuantity(productName, "WH/Stock", "30");
        await inventoryPage.expectOnHandQuantityRow(productName, "WH/Stock", "30");
    });

    test("New Warehouse - Add On-Hand Quantity At Its Stock Location", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouseName = `WH Qty ${key}`;
        const warehouseCode = `WQ${key}`.slice(-5);
        const productName = `E2E WH Qty ${key}`;

        await inventoryPage.createWarehouse({
            name: warehouseName,
            code: warehouseCode,
            receptionStep: 1,
            deliveryStep: 1,
        });

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "25",
        });

        // Auto-created stock location for a 1-step warehouse is "<CODE>/Stock".
        const stockLocation = `${warehouseCode}/Stock`;
        await inventoryPage.addOnHandQuantity(productName, stockLocation, "50");
        await inventoryPage.expectOnHandQuantityRow(productName, warehouseCode, "50");
    });

    test("Receipt Validation Reflects In Product In/Out Tab And Quantities", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Move Flow ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "30",
        });

        // Bring stock in via a validated receipt.
        await inventoryPage.receiptFullFlow({
            productName,
            demand: "12",
        });

        // Quantities tab should now show the product is on-hand somewhere.
        await inventoryPage.gotoProductQuantitiesTab(productName);

        // In/Out tab should show at least one move row for the validated receipt.
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");
    });

    test("Delivery Validation Adds An Outgoing Row To Product In/Out Tab", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Out Move ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "40",
        });

        // Receipt -> stock available, then Delivery -> outgoing row.
        await inventoryPage.receiptFullFlow({
            productName,
            demand: "10",
        });
        const movesAfterReceipt = await inventoryPage.countProductMoveRows(productName);

        await inventoryPage.deliveryFullFlow({
            productName,
            demand: "4",
        });
        const movesAfterDelivery = await inventoryPage.countProductMoveRows(productName);

        // The delivery should have added at least one new move row.
        if (movesAfterDelivery <= movesAfterReceipt) {
            throw new Error(
                `Expected moves count to grow after delivery: ${movesAfterReceipt} -> ${movesAfterDelivery}`
            );
        }
    });
});
