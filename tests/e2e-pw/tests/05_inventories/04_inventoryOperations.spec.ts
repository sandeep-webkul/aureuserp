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
});
