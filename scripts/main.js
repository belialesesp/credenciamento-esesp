import { credenciamentoSubmission, registerSubmission } from "./submitions.js";
import {
  validatePassword,
  validateUserEmail,
  validateTeacher,
  validateTechnician,
  validateInterpreter,
  validatePostTeacher,
} from "./validations.js";

export async function handleRegisterSubmission() {
  const form = document.getElementById("registrationForm");
  form.addEventListener("submit", async function (event) {
    event.preventDefault();
    event.stopPropagation();

    const password = document.getElementById("password").value;
    const passwordConfirmation = document.getElementById(
      "password_confirmation"
    ).value;

    const passwordValidation = validatePassword(password, passwordConfirmation);
    if (!passwordValidation) {
      Toastify({
        text: "A senha não cumpre os critérios!",
        className: "rgToast",
        style: {
          background: "red",
        },
      }).showToast();
      return;
    }

    if (form.checkValidity() === false) {
      form.classList.add("was-validated");
      return;
    }

    try {
      const emailValid = await validateUserEmail();
      if (!emailValid) {
        return;
      }

      await registerSubmission();
    } catch (error) {
      console.error(error);
    }
  });
}

export async function handleTeacherSubmission() {
  const form = document.getElementById("docenteForm");
  const loadingOverlay = document.getElementById("loadingOverlay");

  form.addEventListener(
    "submit",
    async function (event) {
      event.preventDefault();

      try {
        const isValid = await validateTeacher(form);
        if (!isValid) return;

        loadingOverlay.style.display = "flex";
        await credenciamentoSubmission(form, loadingOverlay, "docente");
      } catch (error) {
        loadingOverlay.style.display = "none";
        console.error("Erro no processamento: ", error);
      }
    },
    false
  );
}

export async function handleTechnicianSubmission() {
  const form = document.getElementById("technicianForm");
  const loadingOverlay = document.getElementById("loadingOverlay");

  form.addEventListener(
    "submit",
    async function (event) {
      event.preventDefault();

      try {
        const isValid = await validateTechnician(form);
        if (!isValid) return;

        loadingOverlay.style.display = "flex";
        await credenciamentoSubmission(form, loadingOverlay, "technician");
      } catch (error) {
        loadingOverlay.style.display = "none";
        console.error("Erro no processamento: ", error);
      }
    },
    false
  );
}

export async function handleInterpreterSubmission() {
  const form = document.getElementById("interpreterForm");
  const loadingOverlay = document.getElementById("loadingOverlay");

  form.addEventListener(
    "submit",
    async function (event) {
      event.preventDefault();

      try {
        const isValid = await validateInterpreter(form);
        if (!isValid) return;

        loadingOverlay.style.display = "flex";
        await credenciamentoSubmission(form, loadingOverlay, "interpreter");
      } catch (error) {
        loadingOverlay.style.display = "none";
        console.error("Erro no processamento: ", error);
      }
    },
    false
  );
}

export async function handlePostTeacherSubmission() {
  const form = document.getElementById("postGradTeacher");
  const loadingOverlay = document.getElementById("loadingOverlay");

  form.addEventListener(
    "submit",
    async function (event) {
      event.preventDefault();

      try {
        const isValid = await validatePostTeacher(form);
        if (!isValid) return;

        loadingOverlay.style.display = "flex";
        await credenciamentoSubmission(form, loadingOverlay, "postg_teacher");
      } catch (error) {
        loadingOverlay.style.display = "none";
        console.error("Erro no processamento: ", error);
      }
    },
    false
  );
}
