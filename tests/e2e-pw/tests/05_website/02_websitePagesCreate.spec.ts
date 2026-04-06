import { test } from "../../setup";
import { WebsiteManagementPage } from "../../pages/06_websiteManagement";

test.describe("Website Pages - Create", () => {
    test.beforeAll(async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);
        await websitePage.ensureWebsitePluginInstalled();
    });

    test("Create Website Page - Valid Inputs", async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);
        const key = Date.now();
        const title = `E2E Website Page ${key}`;

        await websitePage.createWebsitePage({
            title,
            content: `This is the content for ${title}`,
            metaTitle: `Meta title for ${title}`,
            metaKeywords: `website,e2e,${key}`,
            metaDescription: `Meta description for ${title}`,
            isHeaderVisible: true,
            isFooterVisible: true,
        });

        await websitePage.searchPage(title);
        await websitePage.expectPageListed(title);
    });
});
