let scholarshipIndex = 0;
let disciplinesIndex = 0;

// Load content


function loadEixos() {
  const eixoSelect = document.querySelectorAll(".eixo-select");

  eixoSelect.forEach((select) => {
    select.innerHTML = '<option value="">Carregando...</option>';

    fetch("../backend/api/get_eixo.php")
      .then((response) => response.json())
      .then((data) => {
        select.innerHTML = '<option value=""></option>';
        data.forEach((eixo) => {
          const option = document.createElement("option");
          option.value = eixo.id;
          option.textContent = eixo.name;
          select.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Erro ao carregar os eixos:", error);
        select.innerHTML = '<option value="">Erro ao carregar</option>';
      });
  });
}

function loadEstacoes(element) {
  const index = element.name.split("_")[1];

  // Limpar sessões
  const disciplinaSelect = document.getElementById(`disciplina_${index}`);
  const modulosSelect = document.getElementById(`modulos_${index}`);
  const lectureSection = document.getElementById(`lectureSection_${index}`);
  disciplinaSelect.value = "";
  modulosSelect.innerHTML = "";
  modulosSelect.style.display = "none";
  lectureSection.style.display = "none";

  const eixoSelect = document.getElementById(`eixo_${index}`);
  const estacaoSelect = document.getElementById(`estacao_${index}`);
  const eixoId = eixoSelect.value;

  estacaoSelect.innerHTML = '<option value="">Carregando...</option>';

  if (eixoId) {
    fetch(`../backend/api/get_estacao.php?eixo_id=${eixoId}`)
      .then((response) => response.json())
      .then((data) => {
        estacaoSelect.innerHTML = '<option value=""></option>';
        data.forEach((estacao) => {
          const option = document.createElement("option");
          option.value = estacao.id;
          option.textContent = estacao.name;
          estacaoSelect.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Erro ao carregar as estações:", error);
        estacaoSelect.innerHTML = '<option value="">Erro ao carregar</option>';
      });
  } else {
    estacaoSelect.innerHTML =
      '<option value="">Selecione um eixo primeiro</option>';
  }
}

function loadDisciplinas(element) {
  const index = element.name.split("_")[1];

  // Limpar sessões
  const modulosSelect = document.getElementById(`modulos_${index}`);
  const lectureSection = document.getElementById(`lectureSection_${index}`);
  modulosSelect.innerHTML = "";
  modulosSelect.style.display = "none";
  lectureSection.style.display = "none";

  const disciplinaSelect = document.getElementById(`disciplina_${index}`);
  const estacaoSelect = document.getElementById(`estacao_${index}`);
  const estacaoId = estacaoSelect.value;

  disciplinaSelect.innerHTML = '<option value="">Carregando...</option>';

  if (estacaoId) {
    fetch(`../backend/api/get_disciplina.php?estacao_id=${estacaoId}`)
      .then((response) => response.json())
      .then((data) => {
        disciplinaSelect.innerHTML = '<option value=""></option>';
        data.forEach((estacao) => {
          const option = document.createElement("option");
          option.value = estacao.id;
          option.textContent = estacao.name;
          disciplinaSelect.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Erro ao carregar as estações:", error);
        disciplinaSelect.innerHTML =
          '<option value="">Erro ao carregar</option>';
      });
  } else {
    disciplinaSelect.innerHTML =
      '<option value="">Selecione um eixo primeiro</option>';
  }
}

function loadModulos(element) {
  const index = element.name.split("_")[1];

  const disciplinaSelect = document.getElementById(`disciplina_${index}`);
  const modulosSelect = document.getElementById(`modulos_${index}`);
  const lectureSection = document.getElementById(`lectureSection_${index}`);

  const disciplinaId = disciplinaSelect.value;

  // Limpar seleção anterior de módulos
  modulosSelect.innerHTML = "";
  modulosSelect.style.display = "none";
  lectureSection.style.display = "none";

  if (element.value == "95") {
    lectureSection.style.display = "block";
  } else {
    if (disciplinaId) {
      fetch(`../backend/api/get_modules.php?disciplina_id=${disciplinaId}`)
        .then((response) => response.json())
        .then((data) => {
          if (data.length > 0) {
            modulosSelect.style.display = "block";

            data.forEach((modulo) => {
              const option = document.createElement("option");
              option.value = modulo.id;
              option.textContent = modulo.name;
              modulosSelect.appendChild(option);
            });

            // Ativar seleção múltipla com biblioteca (opcional)
            $(modulosSelect).select2({
              placeholder: "Selecione os módulos",
              allowClear: true,
            });
          }
        })
        .catch((error) => {
          console.error("Erro ao carregar os módulos:", error);
        });
    }
  }
}

// Load content - Postgraduation

function loadPostGrads() {
  const postGradSelects = document.querySelectorAll(".postGrad-select");

  postGradSelects.forEach((select) => {
    select.innerHTML = '<option value="">Carregando...</option>';

    fetch("../backend/api/get_all_postgrads.php")
      .then((response) => response.json())
      .then((data) => {
        select.innerHTML = '<option value=""></option>';
        data.forEach((postGrad) => {
          const option = document.createElement("option");
          option.value = postGrad.id;
          option.textContent = postGrad.name;
          select.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Erro ao carregar os eixos:", error);
        select.innerHTML = '<option value="">Erro ao carregar</option>';
      });
  });
}

function loadPostGEixos(element) {
  const index = element.name.split("_")[1];

  const postGradSelect = document.getElementById(`postGrad_${index}`);
  const eixoSelect = document.getElementById(`postGEixo_${index}`);
  const postGradId = postGradSelect.value;

  eixoSelect.innerHTML = '<option value="">Carregando...</option>';

  if (postGradId) {
    // Requisição para obter as estações relacionadas ao eixo
    fetch(`../backend/api/get_postg_eixo.php?postg_id=${postGradId}`)
      .then((response) => response.json())
      .then((data) => {
        console.log(data);
        eixoSelect.innerHTML = '<option value=""></option>';
        data.forEach((eixo) => {
          const option = document.createElement("option");
          option.value = eixo.id;
          option.textContent = eixo.name;
          eixoSelect.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Erro ao carregar as estações:", error);
        estacaoSelect.innerHTML = '<option value="">Erro ao carregar</option>';
      });
  } else {
    estacaoSelect.innerHTML =
      '<option value="">Selecione um eixo primeiro</option>';
  }
}

function loadPostGDisciplinas(element) {
  const index = element.name.split("_")[1];
  const disciplinaSelect = document.getElementById(`disciplina_${index}`);
  const eixoSelect = document.getElementById(`postGEixo_${index}`);
  const eixoId = eixoSelect.value;

  disciplinaSelect.innerHTML = '<option value="">Carregando...</option>';

  if (eixoId) {
    // Requisição para obter as estações relacionadas ao eixo
    fetch(`../backend/api/get_postg_disciplina.php?eixo_id=${eixoId}`)
      .then((response) => response.json())
      .then((data) => {
        disciplinaSelect.innerHTML = '<option value=""></option>';
        data.forEach((eixo) => {
          const option = document.createElement("option");
          option.value = eixo.id;
          option.textContent = eixo.name;
          disciplinaSelect.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Erro ao carregar as estações:", error);
        disciplinaSelect.innerHTML =
          '<option value="">Erro ao carregar</option>';
      });
  } else {
    disciplinaSelect.innerHTML =
      '<option value="">Selecione um eixo primeiro</option>';
  }
}

// Load content - Specific Demands

function loadSpcInstitutions() {
  const institutionsSelect = document.querySelectorAll(".institution-select");

  institutionsSelect.forEach((select) => {
    select.innerHTML = '<option value="">Carregando...</option>';

    fetch("../backend/api/get_all_spc_institutions.php")
      .then((response) => response.json())
      .then((data) => {
        select.innerHTML = '<option value=""></option>';
        data.forEach((institution) => {
          const option = document.createElement("option");
          option.value = institution.id;
          option.textContent = institution.name;
          select.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Erro ao carregar os órgãos:", error);
        select.innerHTML = '<option value="">Erro ao carregar</option>';
      });
  });
}

function loadSpcCourses(element) {
  const index = element.name.split("_")[1];

  const institutionSelect = document.getElementById(`institution_${index}`);
  const courseSelect = document.getElementById(`course_${index}`);
  const institutionId = institutionSelect.value;

  courseSelect.innerHTML = '<option value="">Carregando...</option>';

  if (institutionId) {
    fetch(
      `../backend/api/get_all_spc_courses.php?institution_id=${institutionId}`
    )
      .then((response) => response.json())
      .then((data) => {
        courseSelect.innerHTML = '<option value=""></option>';
        data.forEach((course) => {
          const option = document.createElement("option");
          option.value = course.id;
          option.textContent = course.name;
          courseSelect.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Erro ao carregar as estações:", error);
        courseSelect.innerHTML = '<option value="">Erro ao carregar</option>';
      });
  } else {
    courseSelect.innerHTML =
      '<option value="">Selecione um eixo primeiro</option>';
  }
}

// Forms utilities

function cloneSection(section) {
  const container = document.getElementById(`${section}Container`);
  const template = container.querySelector(`.${section}-content`);
  const addBtn = document.getElementById(`${section}Btn`);

  // Clonar o template
  const clone = template.cloneNode(true);
  if (section == "scholarship") {
    scholarshipIndex++;
  } else {
    disciplinesIndex++;

    const moduleSection = clone.querySelector(".module-section");
    moduleSection.remove();
  }

  let index = section == "scholarship" ? scholarshipIndex : disciplinesIndex;

  // Atualizar os nomes dos inputs do clone
  clone.querySelectorAll("select, input").forEach((input) => {
    const baseName = input.name.split("_")[0];

    input.name = `${baseName}_${index}`;
    input.id = `${baseName}_${index}`;
    input.value = "";
  });

  const newModuleSection = document.createElement("div");
  newModuleSection.classList.add("row", "module-section");
  newModuleSection.innerHTML = `<div class="row" >
              <div class="did-floating-label-content col-12">
                <select
                  name="modulos_${index}[]"
                  style="display: none;"
                  id="modulos_${index}"
                  class="did-floating-select form-select modulos"
                  multiple
                  
                >
                </select>
              </div>
            </div>`;

  clone.appendChild(newModuleSection);

  const excludeBtn = document.createElement("button");
  excludeBtn.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
      <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/>
    </svg>
    `;
  excludeBtn.type = "button";
  excludeBtn.classList.add("trash-btn");
  excludeBtn.title = "Excluir";

  clone.appendChild(excludeBtn);

  container.insertBefore(clone, addBtn);
  excludeBtn.onclick = () => clone.remove();
}

function removeInvalid(element) {
  element.classList.remove("invalid");
}

document.addEventListener("DOMContentLoaded", () => {
  // Aplica a máscara de CPF
  Inputmask("999.999.999-99").mask("#cpf");

  // Aplica a máscara de CEP
  Inputmask("99999-999").mask("#zipCode");

  // Aplica a máscara de Telefone
  Inputmask("(99) 99999-9999").mask("#phone");
});

function showSpecialNeeds(show) {
  const inputContainer = document.getElementById(
    "specialNeedsDetailsContainer"
  );
  const inputField = document.getElementById("specialNeedsDetails");

  if (show) {
    inputContainer.style.display = "block";
    inputField.setAttribute("required", "required");
  } else {
    inputContainer.style.display = "none";
    inputField.removeAttribute("required");
    inputField.value = "";
  }
}
