export const CSRF_TOKEN = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

export const SALES_PIPELINE_ROUTES = {
    pipelinesIndex: "/admin/sales-pipeline/pipelines",
    pipelinesStore: "/admin/sales-pipeline/pipelines",
    pipelinesBaseUrl: "/admin/sales-pipeline/pipelines",
    stagesBaseUrl: "/admin/sales-pipeline/stages",
    opportunitiesIndex: "/admin/sales-pipeline/opportunities",
    opportunitiesStore: "/admin/sales-pipeline/opportunities",
    opportunitiesBaseUrl: "/admin/sales-pipeline/opportunities",
    customerIndex: "/admin/customer",
    productIndex: "/admin/product/search?q=",
};
