import { SALES_PIPELINE_ROUTES, CSRF_TOKEN } from '../common/constants.js';
import { fetchData } from '../data/init.js';

const newPipelineForm = document.getElementById("newPipelineForm");

export function initNewPipelineForm() {
    newPipelineForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.is_default = formData.has("is_default");

        try {
            const response = await fetch(SALES_PIPELINE_ROUTES.pipelinesStore, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error("Failed to create pipeline");
            }

            const pipeline = await response.json();

            bootstrap.Modal.getInstance(
                document.getElementById("managePipelinesModal")
            ).hide();
            await fetchData();
            this.reset();

            window.showToast(
                "Success",
                "Pipeline created successfully!",
                "success"
            );
        } catch (error) {
            console.error("Error creating pipeline:", error);
            window.showToast(
                "Error",
                "Failed to create pipeline. Please try again.",
                "error"
            );
        }
    });
}
