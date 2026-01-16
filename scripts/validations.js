function checkDocuments() {
  const fileInput = document.getElementById("documents");
  const file = fileInput.files[0];

  if (!file) {
    return false;
  }

  if (file.type !== "application/pdf") {
    Toastify({
      text: "O documento deve ser em formato pdf",
      className: "rgToast",
      style: {
        background: "red",
      },
    }).showToast();
    return false;
  }

  return true;
}

function validateActivities() {
  const checkboxes = document.querySelectorAll('input[name="position[]"]');
  const atLeastOneChecked = Array.from(checkboxes).some(
    (checkbox) => checkbox.checked
  );

  if (!atLeastOneChecked) {
    return false;
  }

  return true;
}

export async function validateTeacher(form) {
  if (form.checkValidity() === false) {
    form.classList.add("was-validated");
    return;
  }

  const checkDocument = checkDocuments();
  if (!checkDocument) {
    form.classList.add("was-validated");
    return false;
  }

  const checkActivities = validateActivities();
  if (!checkActivities) {
    Toastify({
      text: "Selecione pelo menos uma categoria ou atividade",
      className: "rgToast",
      style: {
        background: "red",
      },
    }).showToast();
    return false;
  }

  const cpfValid = await validateCpf("teacher");
  if (!cpfValid) {
    return false;
  }

  return true;
}

export async function validateSpcDemandTeacher(form) {
  if (form.checkValidity() === false) {
    form.classList.add("was-validated");
    return;
  }

  const checkDocument = checkDocuments();
  if (!checkDocument) {
    form.classList.add("was-validated");
    return false;
  }

  const checkActivities = validateActivities();
  if (!checkActivities) {
    Toastify({
      text: "Selecione pelo menos uma categoria ou atividade",
      className: "rgToast",
      style: {
        background: "red",
      },
    }).showToast();
    return false;
  }

  const cpfValid = await validateCpf("spc_teacher");
  if (!cpfValid) {
    return false;
  }

  return true;
}

export async function validateTechnician(form) {
  if (form.checkValidity() === false) {
    form.classList.add("was-validated");
    return;
  }

  const checkDocument = checkDocuments();
  if (!checkDocument) {
    form.classList.add("was-validated");
    return;
  }

  const cpfValid = await validateCpf("technician");
  if (!cpfValid) {
    return;
  }

  return true;
}

export async function validateInterpreter(form) {
  if (form.checkValidity() === false) {
    form.classList.add("was-validated");
    return;
  }

  const checkDocument = checkDocuments();
  if (!checkDocument) {
    form.classList.add("was-validated");
    return;
  }

  const cpfValid = await validateCpf("interpreter");
  if (!cpfValid) {
    return;
  }

  return true;
}

export async function validatePostTeacher(form) {
  if (form.checkValidity() === false) {
    form.classList.add("was-validated");
    return;
  }

  const checkDocument = checkDocuments();
  if (!checkDocument) {
    form.classList.add("was-validated");
    return;
  }

  const cpfValid = await validateCpf("postg_teacher");
  if (!cpfValid) {
    return;
  }

  return true;
}

export function validatePassword(password, confirmation) {
  if (password.length < 8) {
    return false;
  }

  if (!/[A-Z]/.test(password)) {
    console.log("letra maiuscula");
    return false;
  }

  if (!/[a-z]/.test(password)) {
    console.log("letra minuscula");
    return false;
  }

  if (!/[0-9]/.test(password)) {
    console.log("numero");
    return false;
  }

  if (!/[@$!%*?&+]/.test(password)) {
    console.log("simbolo");
    return false;
  }

  if (password !== confirmation) {
    console.log("senha igual");
    return false;
  }

  return true;
}

function validateCpf(position) {
  const cpf = $("#cpf").val();

  return new Promise((resolve, reject) => {
    $.ajax({
      url: `../backend/validators/validate_${position}_cpf.php`,
      type: "POST",
      data: { cpf: cpf },
      dataType: "json",
      success: function (response) {
        if (response.exists) {
          Toastify({
            text: "CPF já cadastrado!",
            className: "rgToast",
            style: {
              background: "red",
            },
          }).showToast();
          resolve(false);
        } else {
          resolve(true);
        }
      },
      error: function () {
        alert("Erro ao verificar o CPF. Tente novamente.");
        reject(false); // Erro na validação
      },
    });
  });
}

export function validateUserEmail() {
  const email = $("#email").val();

  return new Promise((resolve, reject) => {
    $.ajax({
      url: "../backend/validators/validate_user_email.php",
      type: "POST",
      data: { email: email },
      dataType: "json",
      success: function (response) {
        if (response.exists) {
          Toastify({
            text: "Email já cadastrado!",
            className: "rgToast",
            style: {
              background: "red",
            },
          }).showToast();
          resolve(false);
        } else {
          resolve(true);
        }
      },
      error: function () {
        alert("Erro ao verificar o email. Tente novamente.");
        reject(false);
      },
    });
  });
}
