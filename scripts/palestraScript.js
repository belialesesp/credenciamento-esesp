import { credenciamentoSubmission } from "./submitions.js";

// Referências aos elementos do DOM
const cpfInput = document.getElementById("cpf");
const btnDocente = document.getElementById("btnDocente");
const btnDocentePos = document.getElementById("btnDocentePos");
const palestraForm = document.getElementById("palestraForm");
const welcomeSection = document.getElementById("welcomeSection");
const notRegisteredSection = document.getElementById("notRegisteredSection");
const teacherNameSpan = document.getElementById("teacherName");
const teacherIdInput = document.getElementById("teacher_id");
const teacherTypeInput = document.getElementById("teacher_type");

export function searchCpf() {
  let docenteType = "";

  btnDocente.addEventListener("click", function () {
    docenteType = "teacher";
    checkTeacherRegistration(docenteType);
  });

  btnDocentePos.addEventListener("click", function () {
    docenteType = "teacher_pos";
    checkTeacherRegistration(docenteType);
  });
}

function checkTeacherRegistration(type) {
  const cpf = cpfInput.value.trim();

  if (!cpf) {
    alert("Por favor, informe o CPF.");
    return;
  }

  document.getElementById("loadingOverlay").style.display = "flex";

  const xhr = new XMLHttpRequest();
  xhr.open("POST", `../backend/validators/check_${type}.php`, true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onload = function () {
    document.getElementById("loadingOverlay").style.display = "none";

    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);

        if (response.success && response.found) {
          showForm(response.teacher, type);
        } else {
          hideForm();
          notRegisteredSection.style.display = "block";
        }
      } catch (e) {
        console.error("Erro ao processar resposta:", e);
        alert(
          "Ocorreu um erro ao verificar o CPF. Por favor, tente novamente."
        );
      }
    } else {
      alert("Ocorreu um erro ao verificar o CPF. Por favor, tente novamente.");
    }
  };

  xhr.onerror = function () {
    document.getElementById("loadingOverlay").style.display = "none";
    alert("Ocorreu um erro de conexão. Por favor, tente novamente.");
  };

  xhr.send(
    "cpf=" + encodeURIComponent(cpf) + "&type=" + encodeURIComponent(type)
  );
}

function showForm(teacher, type) {
  teacherNameSpan.textContent = teacher.name;
  teacherIdInput.value = teacher.id;
  teacherTypeInput.value = type;

  welcomeSection.style.display = "block";
  palestraForm.style.display = "block";
  notRegisteredSection.style.display = "none";
}

function hideForm() {
  welcomeSection.style.display = "none";
  palestraForm.style.display = "none";
}

palestraForm.addEventListener(
  "submit",
  async function (e) {
    e.preventDefault();
    const loadingOverlay = document.getElementById("loadingOverlay");

    try {
      loadingOverlay.style.display = "flex";
      await credenciamentoSubmission(palestraForm, loadingOverlay, "palestra");
    } catch (error) {
      loadingOverlay.style.display = "none";
      console.error("Erro no processamento: ", error);
    }
  },
  false
);
