import { test } from "../../setup";
import { WebsiteManagementPage } from "../../pages/06_websiteManagement";

test.describe("Website Pages - Delete", () => {
    test.beforeAll(async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);
        await websitePage.ensureWebsitePluginInstalled();
    });

    test("Delete Website Page - Removes Record", async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);
        const key = Date.now();
        const title = `E2E Website Page ${key}`;

        await websitePage.createWebsitePage({
            title,
            content: `This is the content for ${title}`,
        });

        await websitePage.deleteWebsitePage(title);
        await websitePage.searchPage(title);
        await websitePage.expectPageNotListed(title);
    });
});
