jQuery(document).ready(function ($) {
  const form = $("#dcg-generator-form");
  const deleteForm = $("#dcg-delete-form");
  const progress = $("#dcg-progress");
  const progressBar = $(".progress-bar-fill");
  const progressText = $(".progress-text");
  const results = $("#dcg-results");

  const BATCH_SIZE = 25;

  /**
   * Generate posts in batches
   */
  async function generateInBatches(formData, totalPosts) {
    const batches = Math.ceil(totalPosts / BATCH_SIZE);
    let generatedPosts = [];
    let failedCount = 0;

    for (let i = 0; i < batches; i++) {
      const currentBatchSize = Math.min(BATCH_SIZE, totalPosts - i * BATCH_SIZE);
      const batchFormData = new FormData();

      batchFormData.append("action", "dcg_generate_batch");
      batchFormData.append("nonce", dcgAjax.nonce);
      batchFormData.append("post_type", formData.get("post_type"));
      batchFormData.append("batch_size", currentBatchSize);
      batchFormData.append("batch_index", i);
      batchFormData.append("include_html", formData.get("include_html") ? "true" : "false");
      batchFormData.append("featured_image", formData.get("featured_image") ? "true" : "false");
      batchFormData.append("content_image_count", formData.get("content_image_count") || "0");
      batchFormData.append("generate_categories", formData.get("generate_categories") ? "true" : "false");

      try {
        const response = await $.ajax({
          url: dcgAjax.ajaxurl,
          type: "POST",
          data: batchFormData,
          processData: false,
          contentType: false,
        });

        if (response.success) {
          generatedPosts = generatedPosts.concat(response.data.posts);
        } else {
          failedCount += currentBatchSize;
        }
      } catch (error) {
        failedCount += currentBatchSize;
      }

      // Update progress
      const completed = Math.min((i + 1) * BATCH_SIZE, totalPosts);
      const percent = Math.round((completed / totalPosts) * 100);
      updateProgress(percent, `Generated ${completed} of ${totalPosts} posts...`);
    }

    return { generatedPosts, failedCount };
  }

  /**
   * Update the progress bar and text
   */
  function updateProgress(percent, text) {
    progressBar.css("width", percent + "%");
    progressText.text(text);
  }

  /**
   * Reset progress bar
   */
  function resetProgress() {
    progressBar.css("width", "0%");
    progressText.text("Starting...");
  }

  form.on("submit", async function (e) {
    e.preventDefault();

    const totalPosts = parseInt($("#post_count").val(), 10);

    if (totalPosts < 1) {
      alert("Please enter a valid number of posts.");
      return;
    }

    // Reset UI
    resetProgress();
    progress.show();
    results.hide().empty();

    // Disable form while processing
    form.find("button[type=submit]").prop("disabled", true).text("Generating...");

    // Collect form data
    const formData = new FormData(this);

    try {
      const { generatedPosts, failedCount } = await generateInBatches(formData, totalPosts);

      progress.hide();

      let message = `Successfully generated ${generatedPosts.length} posts.`;
      if (failedCount > 0) {
        message += ` (${failedCount} failed)`;
      }

      results
        .html(
          `<p>${message}</p>` +
          (generatedPosts.length > 0
            ? `<p>Generated post IDs: ${generatedPosts.slice(0, 50).join(", ")}${generatedPosts.length > 50 ? "..." : ""}</p>`
            : "")
        )
        .show();
    } catch (error) {
      progress.hide();
      results
        .html(`<p style="color: #dc3232;">Error: ${error.message || error}</p>`)
        .show();
    }

    // Re-enable form
    form.find("button[type=submit]").prop("disabled", false).text("Generate Content");
  });

  deleteForm.on("submit", function (e) {
    e.preventDefault();

    if (
      !confirm(
        "Are you sure you want to delete all generated content? This action cannot be undone."
      )
    ) {
      return;
    }

    // Reset UI
    resetProgress();
    updateProgress(0, "Deleting generated content...");
    progress.show();
    results.hide().empty();

    // Disable button while processing
    deleteForm.find("button[type=submit]").prop("disabled", true).text("Deleting...");

    // Collect form data
    const formData = new FormData(this);
    formData.append("action", "dcg_delete_content");
    formData.append("nonce", $("#dcg_delete_nonce").val());

    // Send AJAX request
    $.ajax({
      url: dcgAjax.ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        progress.hide();

        if (response.success) {
          results
            .html(`<p>${response.data.message}</p>`)
            .show();
        } else {
          results
            .html(`<p style="color: #dc3232;">Error: ${response.data}</p>`)
            .show();
        }
      },
      error: function (xhr, status, error) {
        progress.hide();
        results
          .html(`<p style="color: #dc3232;">Error: ${error}</p>`)
          .show();
      },
      complete: function () {
        deleteForm.find("button[type=submit]").prop("disabled", false).text("Delete All Generated Content");
      },
    });
  });
});
