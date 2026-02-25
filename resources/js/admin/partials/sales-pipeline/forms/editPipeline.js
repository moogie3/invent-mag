import { SALES_PIPELINE_ROUTES } from '../common/constants.js';
import { fetchData } from '../data/init.js';

const editPipelineForm = document.getElementById("editPipelineForm");

export function initEditPipelineForm() {
    editPipelineForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const pipelineId = document.getElementById("editPipelineId").value;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.is_default = formData.has("is_default");

        try {
            const response = await fetch(
                `${SALES_PIPELINE_ROUTES.pipelinesBaseUrl}/${pipelineId}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                    },
                    body: JSON.stringify(data),
                }
            );

            if (!response.ok) {
                throw new Error("Failed to update pipeline");
            }

            bootstrap.Modal.getInstance(
                document.getElementById("editPipelineModal")
            ).hide();
            await fetchData();

            InventMagApp.showToast(
                "Success",
                "Pipeline updated successfully!",
                "success"
            );
        } catch (error) {
            console.error("Error updating pipeline:", error);
            InventMagApp.showToast(
                "Error",
                "Failed to update pipeline. Please try again.",
                "error"
            );
        }
    });
}