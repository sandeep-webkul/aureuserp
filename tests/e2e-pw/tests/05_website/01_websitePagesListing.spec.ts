import { test } from "../../setup";
import { WebsiteManagementPage } from "../../pages/06_websiteManagement";

test.describe("Website Pages - Listing", () => {
    test.beforeAll(async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);
        await websitePage.ensureWebsitePluginInstalled();
    });

    test("Website Pages Listing - Loads Table", async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);

        await websitePage.gotoWebsitePagesPage();
    });
});
