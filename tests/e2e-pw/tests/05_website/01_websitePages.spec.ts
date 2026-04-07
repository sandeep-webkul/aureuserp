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

        await websitePage.gotoWebsitePagesPage();
        await websitePage.searchPage(title);
        await websitePage.expectPageListed(title);
    });

    test("Edit Website Page - Updates Title and Metadata", async ({ adminPage }) => {
        const websitePage = new WebsiteManagementPage(adminPage);
        const key = Date.now();
        const originalTitle = `E2E Website Page ${key}`;
        const updatedTitle = `E2E Website Page Updated ${key}`;

        await websitePage.createWebsitePage({
            title: originalTitle,
            content: `Page content for ${originalTitle}`,
        });

        await websitePage.editWebsitePage(originalTitle, {
            title: updatedTitle,
            content: `Updated content for ${updatedTitle}`,
            metaTitle: `Updated meta title ${key}`,
            metaDescription: `Updated meta description ${key}`,
            isHeaderVisible: false,
            isFooterVisible: false,
        });

        await websitePage.gotoWebsitePagesPage();
        await websitePage.searchPage(updatedTitle);
        await websitePage.expectPageListed(updatedTitle);
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
