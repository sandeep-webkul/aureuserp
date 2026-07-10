import { test } from "../../setup";
import { SalesFlowPage } from "../../pages/04_salesFlow";
import { InventoriesManagementPage } from "../../pages/06_inventoriesManagement";

/** Stock location of the seeded default warehouse, where sale orders ship from. */
const DEFAULT_STOCK_LOCATION = "WH/Stock";

/**
 * Enable the inventory settings the sales-to-inventory tests rely on: locations and
 * multi-step warehouses, traceability for lot/serial products, and operations for
 * packages. Each describe calls this from its own beforeAll so a shard that runs only
 * a subset of the describes still provisions what its tests need, keeping CI sharding
 * and fullyParallel runs order-independent.
 */
async function enableSalesInventorySettings(adminPage: import("@playwright/test").Page) {
    const salesPage = new SalesFlowPage(adminPage);
    const inventoryPage = new InventoriesManagementPage(adminPage);

    await salesPage.ensureSalesPluginInstalled();
    await inventoryPage.ensureBaseDependentPluginsInstalled();
    await inventoryPage.enableManageWarehousesToggles();
    await inventoryPage.enableManageTraceabilityToggles();
    await inventoryPage.enableManageOperationsToggles();
}

test.describe("Sales Order Flow E2E", () => {
    test.beforeAll(async ({ adminPage }) => {
        await enableSalesInventorySettings(adminPage);
    });

    test.beforeEach(async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        await salesPage.ensureSalesPluginInstalled();
    });

    test("Quotation Validation - Requires Customer And Product", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        await salesPage.gotoQuotationsPage();
        await salesPage.erpLocators.salesQuotationCreateButton.click();
        await salesPage.erpLocators.salesQuotationSaveButton.click();
        await salesPage.expectValidationErrors();
    });


    test("Sales Flow - Customer To Invoice (Ordered Quantities)", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const key = Date.now();

        const customerName = `E2E Sales Customer ${key}`;
        const productName = `E2E Sales Product ${key}`;

        await salesPage.createCustomer({
            name: customerName,
            email: `sales.customer+${key}@example.com`,
        });

        await salesPage.createProduct({
            name: productName,
            price: "100",
            invoicePolicy: "order",
        });

        await salesPage.createQuotation({
            customerName,
            productName,
            quantity: "1",
        });

        await salesPage.confirmQuotation();

        await salesPage.expectCreateInvoiceButtonVisible();

        await salesPage.createInvoice();
        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
        
        // const orderRef = salesPage.currentRecordRef();
        // await salesPage.validateFirstDeliveryForCurrentQuotation();
        // await salesPage.gotoOrderEdit(orderRef);

    });

    /**
     * A "Delivered Quantities" order can only be invoiced once its delivery is validated,
     * and a storable product can only be delivered out of stock it actually owns.
     */
    test("Sales Flow - Customer To Invoice (Delivered Quantities)", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);

        const key = Date.now();

        const customerName = `E2E Sales Customer ${key}`;
        const productName = `E2E Sales Product ${key}`;

        await salesPage.createCustomer({
            name: customerName,
            email: `sales.customer+${key}@example.com`,
        });

        await salesPage.createProduct({
            name: productName,
            price: "100",
            invoicePolicy: "delivery",
        });

        await inventoryPage.addOnHandQuantity(productName, DEFAULT_STOCK_LOCATION, "5");

        await salesPage.createQuotation({
            customerName,
            productName,
            quantity: "2",
        });

        await salesPage.confirmQuotation();
        await salesPage.expectCreateInvoiceButtonHidden();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.expectDeliveryCount(1);
        await salesPage.expectDeliveryState("/OUT/", "Ready");
        await inventoryPage.expectReservedQuantityRow(productName, DEFAULT_STOCK_LOCATION, "2");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.validateFirstDeliveryForCurrentQuotation();

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryState("/OUT/", "Done");
        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "2");

        await inventoryPage.expectOnHandQuantityRow(productName, DEFAULT_STOCK_LOCATION, "3");
        await inventoryPage.expectReservedQuantityRow(productName, DEFAULT_STOCK_LOCATION, "0");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectCreateInvoiceButtonVisible();
        await salesPage.createInvoice();

        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
    });

    test("Sales Flow - Send Quotation By Email", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const key = Date.now();

        const customerName = `E2E Sales Customer ${key}`;
        const productName = `E2E Sales Product ${key}`;

        await salesPage.createCustomer({
            name: customerName,
            email: `sales.customer+${key}@example.com`,
        });

        await salesPage.createProduct({
            name: productName,
            price: "100",
        });

        await salesPage.createQuotation({
            customerName,
            productName,
            quantity: "1",
        });

        await salesPage.sendQuotation();
    });
});

test.describe("Sales Flow - Inventory Integration", () => {
    test.beforeAll(async ({ adminPage }) => {
        await enableSalesInventorySettings(adminPage);
    });

    /**
     * A lot-tracked product is received under a lot, sold, and delivered: the reservation
     * picks the lot automatically and the on-hand quantity drops by the delivered amount.
     */
    test("Sales order - lot tracked product", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const customerName = `E2E SO Lot Customer ${key}`;
        const productName = `E2E SO Lot Product ${key}`;
        const lotName = `LOT-SO-${key}`;

        await salesPage.createCustomer({ name: customerName, email: `so.lot+${key}@example.com` });
        await salesPage.createProduct({
            name: productName,
            price: "50",
            invoicePolicy: "delivery",
            tracking: "lot",
        });

        await inventoryPage.receiptWithLotFlow({ productName, demand: "10" }, lotName);
        await inventoryPage.expectLotListed(lotName);
        await inventoryPage.expectOnHandQuantityRow(productName, DEFAULT_STOCK_LOCATION, "10");

        await salesPage.createQuotation({ customerName, productName, quantity: "4" });
        await salesPage.confirmQuotation();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.expectDeliveryCount(1);
        await salesPage.expectDeliveryState("/OUT/", "Ready");
        await inventoryPage.expectReservedQuantityRow(productName, DEFAULT_STOCK_LOCATION, "4");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.validateFirstDeliveryForCurrentQuotation();

        await inventoryPage.expectOnHandQuantityRow(productName, DEFAULT_STOCK_LOCATION, "6");
        await inventoryPage.expectReservedQuantityRow(productName, DEFAULT_STOCK_LOCATION, "0");
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "4");
        await salesPage.createInvoice();
        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
    });

    /**
     * A serial-tracked product is received as individual serials and one unit is sold;
     * the delivery reserves a single serial and the remaining serial stays on hand.
     */
    test("Sales order - serial tracked product", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const customerName = `E2E SO Serial Customer ${key}`;
        const productName = `E2E SO Serial Product ${key}`;
        const serialPrefix = `SN-SO-${key}`;

        await salesPage.createCustomer({ name: customerName, email: `so.serial+${key}@example.com` });
        await salesPage.createProduct({
            name: productName,
            price: "80",
            invoicePolicy: "delivery",
            tracking: "serial",
        });

        await inventoryPage.receiptWithLotFlow({ productName, demand: "2" }, serialPrefix);
        await inventoryPage.expectLotListed(serialPrefix);

        await salesPage.createQuotation({ customerName, productName, quantity: "1" });
        await salesPage.confirmQuotation();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.expectDeliveryCount(1);
        await salesPage.expectDeliveryState("/OUT/", "Ready");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.validateFirstDeliveryForCurrentQuotation();

        await inventoryPage.expectProductQuantityRowVisible(productName);
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "1");
        await salesPage.createInvoice();
        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
    });

    /**
     * The delivery of a sale order is packed into a package before validation, so the
     * shipped goods are tracked inside that package.
     */
    test("Sales order - delivery packed into a package", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const customerName = `E2E SO Pack Customer ${key}`;
        const productName = `E2E SO Pack Product ${key}`;
        const packageTypeName = `E2E SO PkgType ${key}`;
        const packageName = `E2E SO PKG ${key}`;

        await salesPage.createCustomer({ name: customerName, email: `so.pack+${key}@example.com` });
        await salesPage.createProduct({ name: productName, price: "40", invoicePolicy: "delivery" });

        await inventoryPage.createPackageType({ name: packageTypeName });
        await inventoryPage.createPackage({ name: packageName, packageType: packageTypeName });
        await inventoryPage.addOnHandQuantity(productName, DEFAULT_STOCK_LOCATION, "6");

        await salesPage.createQuotation({ customerName, productName, quantity: "3" });
        await salesPage.confirmQuotation();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.openDeliveryByIndex(0);
        await inventoryPage.setResultPackageOnMove(packageName);
        await salesPage.validateOpenDelivery();

        await inventoryPage.expectPackageContainsProduct(packageName, productName, "3");
        await inventoryPage.expectOnHandQuantityRow(productName, DEFAULT_STOCK_LOCATION, "3");
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "3");
        await salesPage.createInvoice();
        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
    });

    /**
     * One sale order ships four lines at once: a quantity-tracked product, a lot-tracked
     * product, a serial-tracked product, and a fourth line packed into a package.
     */
    test("Sales order - mixed lot, serial, package & quantity lines", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const customerName = `E2E SO Mixed Customer ${key}`;
        const qtyProduct = `E2E SO Mix Qty ${key}`;
        const lotProduct = `E2E SO Mix Lot ${key}`;
        const serialProduct = `E2E SO Mix Serial ${key}`;
        const packedProduct = `E2E SO Mix Packed ${key}`;
        const packageTypeName = `E2E SO MixType ${key}`;
        const packageName = `E2E SO MixPkg ${key}`;

        await salesPage.createCustomer({ name: customerName, email: `so.mixed+${key}@example.com` });
        await salesPage.createProduct({ name: qtyProduct, price: "10", invoicePolicy: "delivery" });
        await salesPage.createProduct({ name: lotProduct, price: "20", invoicePolicy: "delivery", tracking: "lot" });
        await salesPage.createProduct({ name: serialProduct, price: "30", invoicePolicy: "delivery", tracking: "serial" });
        await salesPage.createProduct({ name: packedProduct, price: "15", invoicePolicy: "delivery" });

        await inventoryPage.createPackageType({ name: packageTypeName });
        await inventoryPage.createPackage({ name: packageName, packageType: packageTypeName });

        await inventoryPage.receiptLinesFullFlow([
            { productName: qtyProduct, demand: "10" },
            { productName: lotProduct, demand: "8", lotName: `LOT-MIX-${key}` },
            { productName: serialProduct, demand: "2", lotName: `SN-MIX-${key}` },
            { productName: packedProduct, demand: "6" },
        ]);

        await salesPage.createOrderWithLines({
            customerName,
            lines: [
                { productName: qtyProduct, quantity: "4" },
                { productName: lotProduct, quantity: "3" },
                { productName: serialProduct, quantity: "1" },
                { productName: packedProduct, quantity: "2" },
            ],
        });
        await salesPage.confirmQuotation();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.expectDeliveryCount(1);
        await salesPage.expectDeliveryState("/OUT/", "Ready");
        await inventoryPage.expectReservedQuantityRow(qtyProduct, DEFAULT_STOCK_LOCATION, "4");
        await inventoryPage.expectReservedQuantityRow(lotProduct, DEFAULT_STOCK_LOCATION, "3");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.openDeliveryByIndex(0);
        await inventoryPage.setResultPackageForProduct(packageName, packedProduct);
        await salesPage.validateOpenDelivery();

        await inventoryPage.expectOnHandQuantityRow(qtyProduct, DEFAULT_STOCK_LOCATION, "6");
        await inventoryPage.expectOnHandQuantityRow(lotProduct, DEFAULT_STOCK_LOCATION, "5");
        await inventoryPage.expectOnHandQuantityRow(packedProduct, DEFAULT_STOCK_LOCATION, "4");
        await inventoryPage.expectProductQuantityRowVisible(serialProduct);
        await inventoryPage.expectPackageContainsProduct(packageName, packedProduct, "2");

        await inventoryPage.expectProductMoveRowVisible(qtyProduct, "Done");
        await inventoryPage.expectProductMoveRowVisible(lotProduct, "Done");
        await inventoryPage.expectProductMoveRowVisible(serialProduct, "Done");
        await inventoryPage.expectProductMoveRowVisible(packedProduct, "Done");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "4");
        await salesPage.expectDeliveredQuantity(1, "3");
        await salesPage.expectDeliveredQuantity(2, "1");
        await salesPage.expectDeliveredQuantity(3, "2");
        await salesPage.createInvoice();
        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
    });

    /**
     * A sale order shipped from a 2-step warehouse starts with a Pick. The chain is built
     * lazily — the Ship transfer only appears once the Pick is validated — and the
     * delivered quantity moves only when the Ship reaches the customer location.
     */
    test("Sales order - 2-step delivery warehouse (pick, ship)", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const customerName = `E2E SO 2Step Customer ${key}`;
        const productName = `E2E SO 2Step Product ${key}`;
        const warehouseName = `SO Out2Step ${key}`;
        const warehouseCode = `SO2${key}`;

        await inventoryPage.createWarehouse({
            name: warehouseName,
            code: warehouseCode,
            receptionStep: 1,
            deliveryStep: 2,
        });

        await salesPage.createCustomer({ name: customerName, email: `so.2step+${key}@example.com` });
        await salesPage.createProduct({ name: productName, price: "25", invoicePolicy: "delivery" });
        await inventoryPage.addOnHandQuantity(productName, `${warehouseCode}/Stock`, "10");

        await salesPage.createOrderWithLines({
            customerName,
            warehouseName,
            lines: [{ productName, quantity: "4" }],
        });
        await salesPage.confirmQuotation();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.expectDeliveryCount(1);
        await salesPage.expectDeliveryState("/PICK/", "Ready");
        await inventoryPage.expectReservedQuantityRow(productName, `${warehouseCode}/Stock`, "4");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.openDeliveryByReference("/PICK/");
        await salesPage.validateDeliveryChain(2);

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryCount(2);
        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryState("/PICK/", "Done");
        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryState("/OUT/", "Done");

        await inventoryPage.expectOnHandQuantityRow(productName, `${warehouseCode}/Stock`, "6");
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "4");
        await salesPage.createInvoice();
        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
    });

    /**
     * A sale order shipped from a 3-step warehouse walks Pick, Pack and Ship. Each
     * transfer is created only once its predecessor is validated, so the chain is
     * followed through the Next Transfer action until the goods reach the customer.
     */
    test("Sales order - 3-step delivery warehouse (pick, pack, ship)", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const customerName = `E2E SO 3Step Customer ${key}`;
        const productName = `E2E SO 3Step Product ${key}`;
        const warehouseName = `SO Out3Step ${key}`;
        const warehouseCode = `SO3${key}`;

        await inventoryPage.createWarehouse({
            name: warehouseName,
            code: warehouseCode,
            receptionStep: 1,
            deliveryStep: 3,
        });

        await salesPage.createCustomer({ name: customerName, email: `so.3step+${key}@example.com` });
        await salesPage.createProduct({ name: productName, price: "35", invoicePolicy: "delivery" });
        await inventoryPage.addOnHandQuantity(productName, `${warehouseCode}/Stock`, "12");

        await salesPage.createOrderWithLines({
            customerName,
            warehouseName,
            lines: [{ productName, quantity: "5" }],
        });
        await salesPage.confirmQuotation();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.expectDeliveryCount(1);
        await salesPage.expectDeliveryState("/PICK/", "Ready");
        await inventoryPage.expectReservedQuantityRow(productName, `${warehouseCode}/Stock`, "5");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.openDeliveryByReference("/PICK/");
        await salesPage.validateDeliveryChain(3);

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryCount(3);
        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryState("/PICK/", "Done");
        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryState("/PACK/", "Done");
        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryState("/OUT/", "Done");

        await inventoryPage.expectOnHandQuantityRow(productName, `${warehouseCode}/Stock`, "7");
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "5");
        await salesPage.createInvoice();
        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
    });

    /**
     * Selling more than is on hand reserves only what exists. Validating the short
     * delivery prompts for a back order, and confirming it leaves the order with a
     * second transfer carrying the undelivered remainder.
     */
    test("Sales order - partial delivery creates backorder", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const customerName = `E2E SO Backorder Customer ${key}`;
        const productName = `E2E SO Backorder Product ${key}`;

        await salesPage.createCustomer({ name: customerName, email: `so.backorder+${key}@example.com` });
        await salesPage.createProduct({ name: productName, price: "60", invoicePolicy: "delivery" });
        await inventoryPage.addOnHandQuantity(productName, DEFAULT_STOCK_LOCATION, "4");

        await salesPage.createQuotation({ customerName, productName, quantity: "10" });
        await salesPage.confirmQuotation();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.expectDeliveryCount(1);
        await inventoryPage.expectReservedQuantityRow(productName, DEFAULT_STOCK_LOCATION, "4");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.openDeliveryByIndex(0);
        await salesPage.validateOpenDeliveryCreatingBackorder();

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveryCount(2);

        // The back order carries the six units that could not be shipped.
        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.openPendingDelivery();
        await inventoryPage.expectOperationMoveDemand("6");

        await inventoryPage.expectProductMoveRowVisible(productName, "Done");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "4");
        await salesPage.expectCreateInvoiceButtonVisible();
        await salesPage.createInvoice();
        await salesPage.openInvoicesForCurrentQuotation();
        await salesPage.expectInvoiceRowPresent();
    });

    /**
     * Returning a validated sale-order delivery creates the reverse incoming transfer.
     * Validating it puts the goods back on hand, but the return's move is not tied to the
     * sale order line, so the order keeps its delivered quantity and stays invoiceable.
     */
    test("Sales order - return delivered goods", async ({ adminPage }) => {
        const salesPage = new SalesFlowPage(adminPage);
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();

        const customerName = `E2E SO Return Customer ${key}`;
        const productName = `E2E SO Return Product ${key}`;

        await salesPage.createCustomer({ name: customerName, email: `so.return+${key}@example.com` });
        await salesPage.createProduct({ name: productName, price: "45", invoicePolicy: "delivery" });
        await inventoryPage.addOnHandQuantity(productName, DEFAULT_STOCK_LOCATION, "10");

        await salesPage.createQuotation({ customerName, productName, quantity: "3" });
        await salesPage.confirmQuotation();

        const orderRef = salesPage.currentRecordRef();

        await salesPage.openDeliveryByIndex(0);
        await salesPage.validateOpenDelivery();

        await inventoryPage.expectOnHandQuantityRow(productName, DEFAULT_STOCK_LOCATION, "7");
        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "3");

        await salesPage.openDeliveryByIndex(0);
        await inventoryPage.returnAndValidate();
        await inventoryPage.expectOnReturnOperationPage();
        await inventoryPage.expectOperationDone();

        await inventoryPage.expectOnHandQuantityRow(productName, DEFAULT_STOCK_LOCATION, "10");
        await inventoryPage.expectProductMoveRowVisible(productName, "Done");

        await salesPage.gotoOrderEdit(orderRef);
        await salesPage.expectDeliveredQuantity(0, "3");
        await salesPage.expectCreateInvoiceButtonVisible();
    });
});
