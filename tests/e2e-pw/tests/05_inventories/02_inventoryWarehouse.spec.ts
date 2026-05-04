import { test } from "../../setup";
import { InventoriesManagementPage } from "../../pages/06_inventoriesManagement";

test.describe("@smoke Inventory Warehouse - 1/2/3 Step Configuration & Auto-Created Records", () => {
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
        // Locations + Multi-step routes must be enabled to expose the
        // reception/delivery step radios on the warehouse form.
        await inventoryPage.enableManageWarehousesToggles();
    });

    test("Warehouse Listing - Loads Table", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.gotoWarehousesPage();
    });

    test("Create Warehouse - One Step (Receive / Deliver)", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const name = `WH 1Step ${key}`;
        const code = `W1${key}`.slice(-5);

        await inventoryPage.createWarehouse({
            name,
            code,
            receptionStep: 1,
            deliveryStep: 1,
        });

        // 1-step warehouse should auto-create:
        //   1 location, 3 operation types, 3 routes, 2 rules
        await inventoryPage.expectWarehouseAutoCreatedCounts(code, name, {
            locations: 1,
            operationTypes: 3,
            routes: 2,
            rules: 2,
        });
    });

    test("Create Warehouse - Two Steps (Receive Then Store / Pick Then Deliver)", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const name = `WH 2Step ${key}`;
        const code = `W2${key}`.slice(-5);

        await inventoryPage.createWarehouse({
            name,
            code,
            receptionStep: 2,
            deliveryStep: 2,
        });

        // 2-step warehouse should auto-create:
        //   3 locations, 6 operation types, 4 routes, 6 rules
        await inventoryPage.expectWarehouseAutoCreatedCounts(code, name, {
            locations: 3,
            operationTypes: 6,
            routes: 3,
            rules: 6,
        });
    });

    test("Create Warehouse - Three Steps (Receive, QC, Store / Pick, Pack, Deliver)", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const name = `WH 3Step ${key}`;
        const code = `W3${key}`.slice(-5);

        await inventoryPage.createWarehouse({
            name,
            code,
            receptionStep: 3,
            deliveryStep: 3,
        });

        // 3-step warehouse should auto-create:
        //   5 locations, 8 operation types, 4 routes, 9 rules
        await inventoryPage.expectWarehouseAutoCreatedCounts(code, name, {
            locations: 5,
            operationTypes: 8,
            routes: 3,
            rules: 9,
        });
    });

    test("Edit Warehouse - Switch From One Step To Three Steps", async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        const key = Date.now();
        const name = `WH Edit ${key}`;
        const code = `WE${key}`.slice(-5);

        await inventoryPage.createWarehouse({
            name,
            code,
            receptionStep: 1,
            deliveryStep: 1,
        });
        
        await inventoryPage.editWarehouseSteps(code, 3, 3);

        // After switching to 3-step the auto-created counts should now match
        // the 3-step expectations: 5 locations, 8 operation types, 4 routes, 9 rules.
        await inventoryPage.expectWarehouseAutoCreatedCounts(code, name, {
            locations: 5,
            operationTypes: 8,
            routes: 3,
            rules: 9,
        });
    });
});
