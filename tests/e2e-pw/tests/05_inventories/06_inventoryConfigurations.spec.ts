import { test } from "../../setup";
import { InventoriesManagementPage } from "../../pages/06_inventoriesManagement";

/**
 * Warehouse step-configuration auto-creation, verified by record presence (not brittle counts).
 */
test.describe("Inventory Warehouse", () => {
    /**
     * Locations + multi-step routes must be enabled to expose the step radios.
     */
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
        await inventoryPage.enableManageWarehousesToggles();
    });

    /**
     * The warehouse listing table renders.
     */
    test("Listing Page", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.gotoWarehousesPage();
    });

    /**
     * A one-step warehouse creates only the single-step records.
     */
    test("Create - One Step (Receive / Deliver)", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouse = {
            name: `WH 1Step ${key}`,
            code: `W1${key}`.slice(-5),
            receptionStep: 1 as const,
            deliveryStep: 1 as const,
        };

        await inventoryPage.createWarehouse(warehouse);
        await inventoryPage.expectWarehouseConfiguration(warehouse);
    });

    /**
     * Two steps add the Input/Output locations, Storage/Pick types and two-step routes.
     */
    test("Create - Two Steps (Receive Then Store / Pick Then Deliver)", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouse = {
            name: `WH 2Step ${key}`,
            code: `W2${key}`.slice(-5),
            receptionStep: 2 as const,
            deliveryStep: 2 as const,
        };

        await inventoryPage.createWarehouse(warehouse);
        await inventoryPage.expectWarehouseConfiguration(warehouse);
    });

    /**
     * Three steps add the Quality Control/Packing Zone locations, QC/Pack types and three-step routes.
     */
    test("Create - Three Steps (Receive, QC, Store / Pick, Pack, Deliver)", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouse = {
            name: `WH 3Step ${key}`,
            code: `W3${key}`.slice(-5),
            receptionStep: 3 as const,
            deliveryStep: 3 as const,
        };

        await inventoryPage.createWarehouse(warehouse);
        await inventoryPage.expectWarehouseConfiguration(warehouse);
    });

    /**
     * Reception and delivery steps are independent: two-step receive, one-step deliver.
     */
    test("Create - Asymmetric Steps (Two-Step Receive / One-Step Deliver)", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouse = {
            name: `WH Mixed ${key}`,
            code: `WX${key}`.slice(-5),
            receptionStep: 2 as const,
            deliveryStep: 1 as const,
        };

        await inventoryPage.createWarehouse(warehouse);
        await inventoryPage.expectWarehouseConfiguration(warehouse);
    });

    /**
     * Editing a one-step warehouse to three steps adds the multi-step records and drops the single-step routes.
     */
    test("Switch From One Step To Three Steps", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const warehouse = {
            name: `WH Edit ${key}`,
            code: `WE${key}`.slice(-5),
            receptionStep: 1 as const,
            deliveryStep: 1 as const,
        };

        await inventoryPage.createWarehouse(warehouse);
        await inventoryPage.expectWarehouseConfiguration(warehouse);

        await inventoryPage.editWarehouseSteps(warehouse.code, 3, 3);
        await inventoryPage.expectWarehouseConfiguration({
            ...warehouse,
            receptionStep: 3,
            deliveryStep: 3,
        });
    });
});

/**
 * Location configuration resource CRUD.
 */
test.describe("Inventory Location", () => {
    /**
     * Locations must be enabled to expose the location and scrap-form selects.
     */
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
        await inventoryPage.enableManageWarehousesToggles();
    });

    /**
     * The Locations listing table renders.
     */
    test("Listing Page", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.gotoLocationsPage();
    });

    /**
     * A location can be created with just a name.
     */
    test("Create Location", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const name = `E2E Location ${Date.now()}`;

        await inventoryPage.createLocation(name);
        await inventoryPage.gotoLocationsPage();
        await inventoryPage.expectListContains(name);
    });

    /**
     * A location can be deleted from its row.
     */
    test("Delete Location", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const name = `E2E Location Del ${Date.now()}`;

        await inventoryPage.createLocation(name);
        await inventoryPage.deleteLocation(name);
    });

    /**
     * Vendor Location type persists.
     */
    test("Type - Vendor Location", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);

        await inventoryPage.createLocationOfType(`E2E Loc Vendor ${Date.now()}`, "supplier");
        await inventoryPage.expectInfolistField("Location Type", "Vendor Location");
    });

    /**
     * View type persists.
     */
    test("Type - View", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);

        await inventoryPage.createLocationOfType(`E2E Loc View ${Date.now()}`, "view");
        await inventoryPage.expectInfolistField("Location Type", "View");
    });

    /**
     * Internal Location type persists.
     */
    test("Type - Internal Location", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);

        await inventoryPage.createLocationOfType(`E2E Loc Internal ${Date.now()}`, "internal");
        await inventoryPage.expectInfolistField("Location Type", "Internal Location");
    });

    /**
     * Customer Location type persists.
     */
    test("Type - Customer Location", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);

        await inventoryPage.createLocationOfType(`E2E Loc Customer ${Date.now()}`, "customer");
        await inventoryPage.expectInfolistField("Location Type", "Customer Location");
    });

    /**
     * Inventory Loss type persists.
     */
    test("Type - Inventory Loss", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);

        await inventoryPage.createLocationOfType(`E2E Loc Inventory ${Date.now()}`, "inventory");
        await inventoryPage.expectInfolistField("Location Type", "Inventory Loss");
    });

    /**
     * Production type persists.
     */
    test("Type - Production", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);

        await inventoryPage.createLocationOfType(`E2E Loc Production ${Date.now()}`, "production");
        await inventoryPage.expectInfolistField("Location Type", "Production");
    });

    /**
     * Transit Location type persists.
     */
    test("Type - Transit Location", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);

        await inventoryPage.createLocationOfType(`E2E Loc Transit ${Date.now()}`, "transit");
        await inventoryPage.expectInfolistField("Location Type", "Transit Location");
    });

    /**
     * A scrap location is selectable as the Scrap Location in a scrap operation.
     */
    test("Scrap location usable in scrap", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const scrapLocation = `E2E Scrap Loc ${key}`;
        const product = `E2E Scrap Product ${key}`;

        await inventoryPage.createScrapLocation(scrapLocation);
        await inventoryPage.createInventoryProduct({ name: product, price: "10" });

        await inventoryPage.createScrapAtLocation(product, "1", scrapLocation);
        await inventoryPage.gotoCurrentOperationView();
        await inventoryPage.expectInfolistField("Destination Location", scrapLocation);
    });

    /**
     * A child location keeps its selected parent.
     */
    test("Parent location persists", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const parent = `E2E Parent ${key}`;
        const child = `E2E Child ${key}`;

        await inventoryPage.createLocation(parent);
        await inventoryPage.createLocationWithParent(child, parent);
        await inventoryPage.expectInfolistField("Parent Location", parent);
    });

    /**
     * A location holding stock cannot be deleted.
     */
    test("Delete blocked when location holds stock", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const location = `E2E Loc Stock ${key}`;
        const product = `E2E Loc Product ${key}`;

        await inventoryPage.createLocation(location);
        await inventoryPage.createInventoryProduct({ name: product, price: "10" });
        await inventoryPage.addOnHandQuantity(product, location, "5");
        await inventoryPage.deleteLocationExpectingBlocked(location);
    });
});

/**
 * Operation-type create-form fields, a real stock transfer, and the dropship
 * setting gate — driven end-to-end and verified on the saved record.
 */
test.describe("Inventory Operation Type", () => {
    /**
     * Locations must be enabled for the operation type's source/destination and
     * dropshipping for the Dropship type.
     */
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
        await inventoryPage.enableManageWarehousesToggles();
        await inventoryPage.enableManageLogisticsToggles();
    });

    /**
     * Receipt type persists.
     */
    test("Type - Receipt", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E OpSrc ${key}`;
        const destination = `E2E OpDst ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createLocation(destination);
        await inventoryPage.createOperationTypeWithFlow({
            name: `E2E OpType Receipt ${key}`,
            sequenceCode: "E2ER",
            type: "incoming",
            sourceLocation: source,
            destinationLocation: destination,
        });
        await inventoryPage.expectInfolistField("Operation Type", "Receipt");
    });

    /**
     * Delivery type persists.
     */
    test("Type - Delivery", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E OpSrc ${key}`;
        const destination = `E2E OpDst ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createLocation(destination);
        await inventoryPage.createOperationTypeWithFlow({
            name: `E2E OpType Delivery ${key}`,
            sequenceCode: "E2ED",
            type: "outgoing",
            sourceLocation: source,
            destinationLocation: destination,
        });
        await inventoryPage.expectInfolistField("Operation Type", "Delivery");
    });

    /**
     * Internal type persists.
     */
    test("Type - Internal", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E OpSrc ${key}`;
        const destination = `E2E OpDst ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createLocation(destination);
        await inventoryPage.createOperationTypeWithFlow({
            name: `E2E OpType Internal ${key}`,
            sequenceCode: "E2EI",
            type: "internal",
            sourceLocation: source,
            destinationLocation: destination,
        });
        await inventoryPage.expectInfolistField("Operation Type", "Internal");
    });

    /**
     * Dropship type persists (requires the dropshipping setting enabled).
     */
    test("Type - Dropship", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E OpSrc ${key}`;
        const destination = `E2E OpDst ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createLocation(destination);
        await inventoryPage.createOperationTypeWithFlow({
            name: `E2E OpType Dropship ${key}`,
            sequenceCode: "E2EDS",
            type: "dropship",
            sourceLocation: source,
            destinationLocation: destination,
        });
        await inventoryPage.expectInfolistField("Operation Type", "Dropship");
    });

    /**
     * The Return Type accepts another operation type.
     */
    test("Return type from another operation type", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E OpSrc ${key}`;
        const destination = `E2E OpDst ${key}`;
        const returnType = `E2E OpType Base ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createLocation(destination);
        await inventoryPage.createOperationTypeWithFlow({
            name: returnType,
            sequenceCode: "E2EBS",
            type: "incoming",
            sourceLocation: source,
            destinationLocation: destination,
        });
        await inventoryPage.createOperationTypeWithFlow({
            name: `E2E OpType Return ${key}`,
            sequenceCode: "E2ERT",
            type: "incoming",
            sourceLocation: source,
            destinationLocation: destination,
            returnTypeName: returnType,
        });
        await inventoryPage.expectInfolistField("Return Operation Type", returnType);
    });

    /**
     * The create-backorder policy persists as chosen.
     */
    test("Create backorder policy persists", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E OpSrc ${key}`;
        const destination = `E2E OpDst ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createLocation(destination);
        await inventoryPage.createOperationTypeWithFlow({
            name: `E2E OpType Backorder ${key}`,
            sequenceCode: "E2EB",
            type: "incoming",
            sourceLocation: source,
            destinationLocation: destination,
            createBackorder: "never",
        });
        await inventoryPage.expectInfolistField("Create Backorder", "Never");
    });

    /**
     * A receipt using a custom operation type lands the product at its destination.
     */
    test("Product moves through a custom operation type", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const destination = `E2E Move Dst ${key}`;
        const operationType = `E2E Move OpType ${key}`;
        const product = `E2E Move Product ${key}`;

        await inventoryPage.createLocation(destination);
        await inventoryPage.createOperationTypeWithFlow({
            name: operationType,
            sequenceCode: "E2EMV",
            type: "incoming",
            destinationLocation: destination,
        });
        await inventoryPage.createInventoryProduct({ name: product, price: "10" });

        await inventoryPage.createReceipt({
            productName: product,
            demand: "7",
            operationTypeName: operationType,
        });
        await inventoryPage.confirmAndValidateOperation();

        await inventoryPage.expectOnHandQuantityRow(product, destination, "7");
    });

    /**
     * Returning a validated operation uses the operation type's configured return
     * type and reverses its locations (source = original destination, destination
     * = the incoming return type's destination).
     */
    test("Return uses the custom return type", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const returnDest = `E2E Ret Dest ${key}`;
        const mainDest = `E2E Main Dest ${key}`;
        const returnType = `E2E Ret Type ${key}`;
        const mainType = `E2E Main Type ${key}`;
        const product = `E2E Ret Product ${key}`;

        await inventoryPage.createLocation(returnDest);
        await inventoryPage.createOperationTypeWithFlow({
            name: returnType,
            sequenceCode: "E2ERA",
            type: "incoming",
            destinationLocation: returnDest,
        });

        await inventoryPage.createLocation(mainDest);
        await inventoryPage.createOperationTypeWithFlow({
            name: mainType,
            sequenceCode: "E2ERB",
            type: "incoming",
            destinationLocation: mainDest,
            returnTypeName: returnType,
        });

        await inventoryPage.createInventoryProduct({ name: product, price: "10" });
        await inventoryPage.createReceipt({
            productName: product,
            demand: "4",
            operationTypeName: mainType,
        });
        await inventoryPage.confirmAndValidateOperation();

        await inventoryPage.returnCurrentOperation();
        await inventoryPage.gotoCurrentOperationView();
        await inventoryPage.expectInfolistField("Operation Type", returnType);
        await inventoryPage.expectInfolistField("Source Location", mainDest);
        await inventoryPage.expectInfolistField("Destination Location", returnDest);
    });

    /**
     * Ask backorder policy: validating a partial delivery opens the modal, and
     * confirming it creates a backorder for the remaining quantity.
     */
    test("Create backorder - Ask creates a backorder", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E BO Src ${key}`;
        const product = `E2E BO Product ${key}`;
        const opType = `E2E BO Ask Type ${key}`;
        const origin = `E2E BO Ask ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createInventoryProduct({ name: product, price: "10" });
        await inventoryPage.addOnHandQuantity(product, source, "5");

        await inventoryPage.createOperationTypeWithFlow({
            name: opType,
            sequenceCode: "E2EBA",
            type: "outgoing",
            sourceLocation: source,
            createBackorder: "ask",
        });

        await inventoryPage.createDelivery({
            productName: product,
            demand: "10",
            operationTypeName: opType,
            origin,
        });
        await inventoryPage.validateCreatingBackorder();

        await inventoryPage.expectDeliveryCountByOrigin(origin, 2);
    });

    /**
     * Never backorder policy: validating a partial delivery shows no modal and
     * creates no backorder, so only the original delivery exists.
     */
    test("Create backorder - Never skips the backorder", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E BO Src ${key}`;
        const product = `E2E BO Product ${key}`;
        const opType = `E2E BO Never Type ${key}`;
        const origin = `E2E BO Never ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createInventoryProduct({ name: product, price: "10" });
        await inventoryPage.addOnHandQuantity(product, source, "5");

        await inventoryPage.createOperationTypeWithFlow({
            name: opType,
            sequenceCode: "E2EBN",
            type: "outgoing",
            sourceLocation: source,
            createBackorder: "never",
        });

        await inventoryPage.createDelivery({
            productName: product,
            demand: "10",
            operationTypeName: opType,
            origin,
        });
        await inventoryPage.validateWithoutBackorderModal();

        await inventoryPage.expectDeliveryCountByOrigin(origin, 1);
    });

    /**
     * Always backorder policy: validating a partial delivery shows no modal and
     * still creates a backorder for the remaining quantity.
     */
    test("Create backorder - Always creates a backorder without asking", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const source = `E2E BO Src ${key}`;
        const product = `E2E BO Product ${key}`;
        const opType = `E2E BO Always Type ${key}`;
        const origin = `E2E BO Always ${key}`;

        await inventoryPage.createLocation(source);
        await inventoryPage.createInventoryProduct({ name: product, price: "10" });
        await inventoryPage.addOnHandQuantity(product, source, "5");

        await inventoryPage.createOperationTypeWithFlow({
            name: opType,
            sequenceCode: "E2EBW",
            type: "outgoing",
            sourceLocation: source,
            createBackorder: "always",
        });

        await inventoryPage.createDelivery({
            productName: product,
            demand: "10",
            operationTypeName: opType,
            origin,
        });
        await inventoryPage.validateWithoutBackorderModal();

        await inventoryPage.expectDeliveryCountByOrigin(origin, 2);
    });

    /**
     * With dropshipping off, the type field must not offer Dropship (fails until
     * the app gates the option — the type select currently always lists it).
     */
    test("Dropship type hidden when dropshipping off", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);

        await inventoryPage.disableDropshipping();
        await inventoryPage.gotoOperationTypeCreatePage();
        await inventoryPage.expectOperationTypeOptionAbsent("dropship");
    });
});
