jQuery(document).ready(function ($) {
  const form = $("#dcg-generator-form");
  const deleteForm = $("#dcg-delete-form");
  const progress = $("#dcg-progress");
  const results = $("#dcg-results");

  form.on("submit", function (e) {
    e.preventDefault();

    // Reset UI
    progress.show();
    results.hide().empty();

    // Collect form data
    const formData = new FormData(this);
    formData.append("action", "dcg_generate_content");
    formData.append("nonce", dcgAjax.nonce);

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
            .html(
              `
                        <p>${response.data.message}</p>
                        <p>Generated post IDs: ${response.data.posts.join(
                          ", "
                        )}</p>
                    `
            )
            .show();
        } else {
          results
            .html(
              `
                        <p style="color: #dc3232;">Error: ${response.data}</p>
                    `
            )
            .show();
        }
      },
      error: function (xhr, status, error) {
        progress.hide();
        results
          .html(
            `
                    <p style="color: #dc3232;">Error: ${error}</p>
                `
          )
          .show();
      },
    });
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
    progress.show();
    results.hide().empty();

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
            .html(
              `
                        <p>${response.data.message}</p>
                    `
            )
            .show();
        } else {
          results
            .html(
              `
                        <p style="color: #dc3232;">Error: ${response.data}</p>
                    `
            )
            .show();
        }
      },
      error: function (xhr, status, error) {
        progress.hide();
        results
          .html(
            `
                    <p style="color: #dc3232;">Error: ${error}</p>
                `
          )
          .show();
      },
    });
  });
});
