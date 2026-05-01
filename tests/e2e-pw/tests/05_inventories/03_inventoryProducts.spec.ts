import { test } from "../../setup";
import { InventoriesManagementPage } from "../../pages/06_inventoriesManagement";

test.describe("Inventory Products - CRUD", () => {
    test.beforeAll(async ({ adminPage }) => {
        const inventoryPage = new InventoriesManagementPage(adminPage);
        await inventoryPage.ensureBaseDependentPluginsInstalled();
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
});
