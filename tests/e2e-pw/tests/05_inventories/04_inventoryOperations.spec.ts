import { test } from "../../setup";
import { InventoriesManagementPage } from "../../pages/06_inventoriesManagement";

test.describe("Inventory Operations - Receipts, Deliveries, Internal Transfers", () => {
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
        // Ensure at minimum: locations + multi-step routes for the source/dest
        // location selectors to render on the operation form.
        await inventoryPage.enableManageWarehousesToggles();
        // Lots & Serial Numbers must be on for the lot-tracked receipt flow.
        await inventoryPage.enableManageTraceabilityToggles();
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

    test("Delivery Full Flow - Create, Validate, Stock Decreases", async ({ adminPage }) => {
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
        const warehouseName = `WH Out ${key}`;
        const warehouseCode = `WO${key}`.slice(-5);
        const productName = `E2E Out Move ${key}`;

        // Use a fresh 1-step warehouse so the receipt's incoming move and the
        // delivery's outgoing move both transition to "Done" in a single step,
        // independent of any multi-step state left by earlier tests.
        await inventoryPage.createWarehouse({
            name: warehouseName,
            code: warehouseCode,
            receptionStep: 1,
            deliveryStep: 1,
        });

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "40",
        });

        await inventoryPage.receiptFullFlow({
            productName,
            demand: "10",
            operationType: warehouseName,
        });

        await inventoryPage.deliveryFullFlow({
            productName,
            demand: "4",
            operationType: warehouseName,
        });

        // After receipt (10) and delivery (4) against the 1-step warehouse, the
        // product's stock location should hold 6 on hand, and the In/Out tab
        // should list both validated moves as "Done".
        await inventoryPage.expectOnHandQuantityRow(productName, warehouseCode, "6");
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");
        await inventoryPage.expectProductQuantityRowVisible(productName);
    });

    test("Receipt With Lot - Generate Lot And Validate", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Lot Receipt ${key}`;
        const lotName = `LOT-${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "30",
            tracking: "lot",
        });

        // Receive the lot-tracked product, generating the lot during validation.
        await inventoryPage.receiptWithLotFlow({ productName, demand: "8" }, lotName);

        // The validated lot receipt should put the product on-hand and log a Done move.
        await inventoryPage.expectProductQuantityRowVisible(productName);
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");
    });

    test("Receipt With Serial - Generate Serials And Validate", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productName = `E2E Serial Receipt ${key}`;
        const serialPrefix = `SN-${key}`;

        await inventoryPage.createInventoryProduct({
            name: productName,
            price: "30",
            tracking: "serial",
        });

        // Receiving 3 units of a serial-tracked product generates 3 serials
        // (one unit each) during validation.
        await inventoryPage.receiptWithLotFlow({ productName, demand: "3" }, serialPrefix);

        await inventoryPage.expectProductQuantityRowVisible(productName);
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");
    });

    test("Receipt flow - 2-step to stock", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouseName = `WH 2Step ${key}`;
        const warehouseCode = `R2${key}`.slice(-5);
        const productName = `E2E 2Step Receipt ${key}`;

        await inventoryPage.createWarehouse({
            name: warehouseName,
            code: warehouseCode,
            receptionStep: 2,
            deliveryStep: 1,
        });
        await inventoryPage.createInventoryProduct({ name: productName, price: "20" });

        // Receive via the warehouse's operation type; validating auto-generates the
        // onward transfer (Input -> Stock), which "Next Transfer" follows to Stock.
        await inventoryPage.receiptChainFullFlow({
            productName,
            demand: "10",
            operationType: warehouseName,
        });

        await inventoryPage.expectOnHandQuantityRow(productName, `${warehouseCode}/Stock`, "10");
    });

    test("Receipt flow - 3-step to stock", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouseName = `WH 3Step ${key}`;
        const warehouseCode = `R3${key}`.slice(-5);
        const productName = `E2E 3Step Receipt ${key}`;

        await inventoryPage.createWarehouse({
            name: warehouseName,
            code: warehouseCode,
            receptionStep: 3,
            deliveryStep: 1,
        });
        await inventoryPage.createInventoryProduct({ name: productName, price: "20" });

        // Receive via the warehouse's operation type; validating auto-generates the
        // onward transfers (Input -> QC -> Stock), followed with "Next Transfer".
        await inventoryPage.receiptChainFullFlow({
            productName,
            demand: "10",
            operationType: warehouseName,
        });

        await inventoryPage.expectOnHandQuantityRow(productName, `${warehouseCode}/Stock`, "10");
    });

    test("Delivery flow - 3-step pick, pack, ship", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouseName = `WH Out3Step ${key}`;
        const warehouseCode = `D3${key}`.slice(-5);
        const productName = `E2E 3Step Delivery ${key}`;

        // 1-step reception (straight to Stock) + 3-step delivery (Pick, Pack, Ship).
        await inventoryPage.createWarehouse({
            name: warehouseName,
            code: warehouseCode,
            receptionStep: 1,
            deliveryStep: 3,
        });
        await inventoryPage.createInventoryProduct({ name: productName, price: "20" });

        // Bring stock straight to Stock (1-step reception).
        await inventoryPage.receiptFullFlow({
            productName,
            demand: "10",
            operationType: warehouseName,
        });

        // Start the outgoing chain with a Pick (Stock -> Packing Zone); validating
        // it auto-generates Pack then Ship, which "Next Transfer" follows until
        // the product ships out to the customer.
        await inventoryPage.internalTransferFullFlow({
            productName,
            demand: "10",
            operationType: warehouseName,
            operationTypeName: "Pick",
        });
        await inventoryPage.chainNextTransfers();

        await inventoryPage.expectProductMoveRowVisible(productName, "Done");
    });

    test("Receipt - multiple products", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const productA = `E2E Multi A ${key}`;
        const productB = `E2E Multi B ${key}`;

        await inventoryPage.createInventoryProduct({ name: productA, price: "10" });
        await inventoryPage.createInventoryProduct({ name: productB, price: "15" });

        await inventoryPage.receiptLinesFullFlow([
            { productName: productA, demand: "5" },
            { productName: productB, demand: "3" },
        ]);

        await inventoryPage.expectProductQuantityRowVisible(productA);
        await inventoryPage.expectProductQuantityRowVisible(productB);
    });

    test("Receipt - mixed lot, serial & quantity", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const lotProduct = `E2E Mix Lot ${key}`;
        const serialProduct = `E2E Mix Serial ${key}`;
        const qtyProduct = `E2E Mix Qty ${key}`;

        await inventoryPage.createInventoryProduct({ name: lotProduct, price: "10", tracking: "lot" });
        await inventoryPage.createInventoryProduct({ name: serialProduct, price: "12", tracking: "serial" });
        await inventoryPage.createInventoryProduct({ name: qtyProduct, price: "8" });

        // One receipt with a lot, a serial and a quantity-tracked line; lot and
        // serials are generated, the quantity line needs nothing extra.
        await inventoryPage.receiptLinesFullFlow([
            { productName: lotProduct, demand: "4", lotName: `LOT-${key}` },
            { productName: serialProduct, demand: "2", lotName: `SN-${key}` },
            { productName: qtyProduct, demand: "5" },
        ]);

        await inventoryPage.expectProductQuantityRowVisible(lotProduct);
        await inventoryPage.expectProductQuantityRowVisible(serialProduct);
        await inventoryPage.expectProductQuantityRowVisible(qtyProduct);
    });
});
