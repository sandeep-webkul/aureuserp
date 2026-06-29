import { test } from "../../setup";
import { InventoriesManagementPage } from "../../pages/06_inventoriesManagement";

test.describe("Inventory Operations - Receipts, Deliveries, Internal Transfers", () => {
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
        // Ensure at minimum: locations + multi-step routes for the source/dest
        // location selectors to render on the operation form.
        await inventoryPage.enableManageWarehousesToggles();
    });

    test("Receipts Listing - Loads Table", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.gotoReceiptsPage();
    });

    test("Deliveries Listing - Loads Table", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.gotoDeliveriesPage();
    });

    test("Internal Transfers Listing - Loads Table", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.gotoInternalTransfersPage();
    });

    test("Create Receipt - Save Draft With One Move Line", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Receipt Product ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "10",
        });

        await inventoryPage.createReceipt({
            productName,
            demand: "5",
        });
    });

    test("Receipt Full Flow - Create, Validate, Stock Increases", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Receipt Flow ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "20",
        });

        await inventoryPage.receiptFullFlow({
            productName,
            demand: "10",
        });

        await inventoryPage.expectProductQuantityRowVisible(productName);
    });

    test("Delivery Full Flow - Create And Validate (Outgoing)", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Delivery Flow ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "30",
        });

        // Bring stock in first so the delivery has something to ship.
        await inventoryPage.receiptFullFlow({
            productName,
            demand: "20",
        });

        await inventoryPage.deliveryFullFlow({
            productName,
            demand: "5",
        });

        await inventoryPage.expectProductQuantityRowVisible(productName);
    });

    test("Internal Transfer - Create And Validate Move", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Internal Flow ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "40",
        });

        await inventoryPage.receiptFullFlow({
            productName,
            demand: "15",
        });

        await inventoryPage.internalTransferFullFlow({
            productName,
            demand: "5",
        });

        await inventoryPage.expectProductQuantityRowVisible(productName);
    });

    test("Saved Receipt Draft - Appears In Receipts List", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Receipt List ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "15",
        });

        const reference = await inventoryPage.createReceipt({
            productName,
            demand: "7",
        });

        // The receipts listing exposes the reference (not the product) as a
        // searchable column, so look the draft up by its generated reference.
        await inventoryPage.gotoReceiptsPage();
        await inventoryPage.expectListContains(reference);
    });

    test("Two Sequential Receipts - Both Reflect On Product Moves Tab", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Two Receipts ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "25",
        });

        await inventoryPage.receiptFullFlow({
            productName,
            demand: "8",
        });
        const movesAfterFirst = await inventoryPage.countProductMoveRows(productName);

        await inventoryPage.receiptFullFlow({
            productName,
            demand: "12",
        });
        const movesAfterSecond = await inventoryPage.countProductMoveRows(productName);

        if (movesAfterSecond <= movesAfterFirst) {
            throw new Error(
                `Expected moves count to grow after second receipt: ${movesAfterFirst} -> ${movesAfterSecond}`
            );
        }
    });

    test("Validated Receipt - Done State Row Visible In Product Moves Tab", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Done State ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "18",
        });

        await inventoryPage.receiptFullFlow({
            productName,
            demand: "9",
        });

        // The validated receipt should produce a "Done" move on the product's tab.
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");
    });

    test("Delivery After Receipt - Outgoing Move Row Visible", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Out Move Op ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "22",
        });

        await inventoryPage.receiptFullFlow({
            productName,
            demand: "20",
        });
        const movesAfterReceipt = await inventoryPage.countProductMoveRows(productName);

        await inventoryPage.deliveryFullFlow({
            productName,
            demand: "6",
        });
        const movesAfterDelivery = await inventoryPage.countProductMoveRows(productName);

        if (movesAfterDelivery <= movesAfterReceipt) {
            throw new Error(
                `Expected moves count to grow after delivery: ${movesAfterReceipt} -> ${movesAfterDelivery}`
            );
        }
    });

    test("Internal Transfer - Move Row Adds To Product Moves Tab", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Internal Moves ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "33",
        });

        await inventoryPage.receiptFullFlow({
            productName,
            demand: "18",
        });
        const movesAfterReceipt = await inventoryPage.countProductMoveRows(productName);

        await inventoryPage.internalTransferFullFlow({
            productName,
            demand: "4",
        });
        const movesAfterTransfer = await inventoryPage.countProductMoveRows(productName);

        if (movesAfterTransfer <= movesAfterReceipt) {
            throw new Error(
                `Expected moves count to grow after internal transfer: ${movesAfterReceipt} -> ${movesAfterTransfer}`
            );
        }
    });

    test("Saved Delivery Draft - Appears In Deliveries List", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Delivery List ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "12",
        });

        const reference = await inventoryPage.createDelivery({
            productName,
            demand: "3",
        });

        await inventoryPage.gotoDeliveriesPage();
        await inventoryPage.expectListContains(reference);
    });

    test("Saved Internal Transfer Draft - Appears In Internal Transfers List", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Internal List ${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "14",
        });

        const reference = await inventoryPage.createInternalTransfer({
            productName,
            demand: "2",
        });

        await inventoryPage.gotoInternalTransfersPage();
        await inventoryPage.expectListContains(reference);
    });
});
