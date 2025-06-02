export async function registerSubmission() {
  try {
    const form = document.getElementById("registrationForm");
    const formData = new FormData(form);
    const response = await fetch("../auth/process_register.php", {
      method: "POST",
      body: formData,
    });

    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      // Se não for JSON, pega o texto para debug
      const text = await response.text();
      console.error("Resposta não-JSON recebida:", text);
      throw new Error("Resposta inválida do servidor");
    }

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || "Submission failed");
    }

    window.location.href =
      "http://localhost/credenciamento-esesp/pages/home.php";
  } catch (error) {
    console.error("Form submission error:", error);
    Toastify({
      text: "Erro ao processar registro: " + error.message,
      className: "rgToast",
      style: {
        background: "red",
      },
    }).showToast();
  }
}

export function credenciamentoSubmission(form, loadingOverlay, category) {
  return new Promise((resolve, reject) => {
    const formData = new FormData(form);
    const xhr = new XMLHttpRequest();

    xhr.open("POST", `../backend/api/post_${category}.php`, true);

    xhr.upload.onprogress = function (event) {
      if (event.lengthComputable) {
        const percentComplete = Math.round((event.loaded / event.total) * 100);
        document.querySelector(".upload-progress").textContent =
          percentComplete + "%";
      }
    };

    xhr.onload = function () {
      try {
        const response = JSON.parse(xhr.responseText);

        if (xhr.status === 200 && response.success) {
          window.location.href = response.redirect_url;
          resolve();
        } else {
          loadingOverlay.style.display = "none";
          console.error("Erro ao enviar o formulário");
          Toastify({
            text: "Ocorreu um erro ao enviar o formulário",
            className: "rgToast",
            style: {
              background: "red",
            },
          }).showToast();
          reject(new Error("Erro ao enviar formulário"));
        }
      } catch (error) {
        loadingOverlay.style.display = "none";
        console.error("Erro ao processar resposta: ", error);
        reject(error);
      }
    };

    xhr.onerror = function () {
      loadingOverlay.style.display = "none";
      reject(new Error("Erro na requisição"));
    };

    xhr.send(formData);
  });
}
