// app/assets/js/professor.js
document.addEventListener('DOMContentLoaded', function() {
    /* ============================================================
       0) FUNÇÃO PARA CARREGAR ANOS LETIVOS NO SELECT (Modal Impressão)
    ============================================================ */
    function carregarAnosLetivos() {
        fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1) Select do Modal Individual
                    const sel = document.getElementById('select-ano-letivo-print');
                    if (sel) {
                        sel.innerHTML = '<option value="">-- Selecione --</option>';
                        data.data.forEach(ano => {
                            sel.innerHTML += `<option value="${ano.id_ano_letivo}">${ano.ano}</option>`;
                        });
                    }

                    // 2) Select do Modal de Impressão Geral (opcional)
                    const selGeral = document.getElementById('select-ano-letivo-print-geral');
                    if (selGeral) {
                        selGeral.innerHTML = '<option value="">-- Selecione --</option>';
                        data.data.forEach(ano => {
                            selGeral.innerHTML += `<option value="${ano.id_ano_letivo}">${ano.ano}</option>`;
                        });
                    }
                }
            })
            .catch(err => console.error(err));
    }

    /* ============================================================
       REFERÊNCIAS GERAIS
    ============================================================ */
    const modalProfessor         = document.getElementById('modal-professor');
    const btnAdd                 = document.getElementById('btn-add');
    const closeModalElements     = document.querySelectorAll('#modal-professor .close-modal');
    const cancelBtn              = document.getElementById('cancel-btn');
    const saveBtn                = document.getElementById('save-btn');

    // Botão que abre o modal "Imprimir Geral"
    const btnOpenPrintGeral      = document.getElementById('btnImprimir');

    // Modal de exclusão (animado)
    const modalDelete            = document.getElementById('modal-delete');
    const closeDeleteModalBtn    = document.getElementById('close-delete-modal');
    const confirmDeleteBtn       = document.getElementById('confirm-delete-btn');
    const cancelDeleteBtn        = document.getElementById('cancel-delete-btn');

    // Tabela de professores
    const professorTableBody     = document.getElementById('professorTable');
    const noDataMessage          = document.getElementById('no-data-message');

    // Modal de Impressão Individual
    const modalPrint             = document.getElementById('modal-print-professor');
    const closePrintProfessorBtn = document.getElementById('close-print-professor');
    const btnImprimirProfessor   = document.getElementById('btn-imprimir');
    const btnCancelarProfessor   = document.getElementById('btn-cancelar');
    let currentPrintProfessorId  = null;

    // Modal de Impressão Geral
    const modalPrintGeral        = document.getElementById('modal-print-geral');
    const closePrintGeral        = document.getElementById('close-print-geral');
    const btnImprimirGeral       = document.getElementById('btn-imprimir-geral');
    const btnCancelarGeral       = document.getElementById('btn-cancelar-geral');

    // Controle de modo edição
    let isEditMode       = false;
    let currentEditId    = null;

    // Campos do formulário
    const inputProfessorId       = document.getElementById('professorId');
    const inputNomeCompleto      = document.getElementById('nome-completo');
    const inputNomeExibicao      = document.getElementById('nome-exibicao');
    const inputTelefone          = document.getElementById('telefone');
    const radioSexoMasc          = document.getElementById('sexo-masc');
    const radioSexoFem           = document.getElementById('sexo-fem');
    const radioSexoOutro         = document.getElementById('sexo-outro');
    const inputLimiteAulas       = document.getElementById('limite-aulas');

    /* ============================================================
       MÁSCARAS E VALIDAÇÕES
    ============================================================ */
    function maskTelefone(value) {
        let v = value.replace(/\D/g, '');
        v = v.substring(0, 11);
        if (v.length <= 10) {
            v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else {
            v = v.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        }
        return v;
    }
    if (inputTelefone) {
        inputTelefone.addEventListener('input', function() {
            this.value = maskTelefone(this.value);
        });
    }

    if (inputLimiteAulas) {
        inputLimiteAulas.addEventListener('input', function() {
            let val = this.value.replace(/\D/g, '');
            if (val.length > 2) {
                val = val.substring(0, 2);
            }
            this.value = val;
        });
    }

    /* ============================================================
       1) LISTAR PROFESSORES
    ============================================================ */
    function fetchProfessores() {
        fetch('/horarios/app/controllers/professor/listProfessor.php')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    renderTable(data.data);
                } else {
                    console.error(data.message);
                }
            })
            .catch(err => console.error(err));
    }

    /* ============================================================
       2) MONTAR TABELA
    ============================================================ */
    function renderTable(rows) {
        professorTableBody.innerHTML = '';
        if (!rows || rows.length === 0) {
            noDataMessage.style.display = 'block';
            return;
        }
        noDataMessage.style.display = 'none';

        rows.forEach(row => {
            const tr = document.createElement('tr');
            tr.dataset.id    = row.id_professor;
            tr.dataset.phone = row.telefone || '';

            // Nome Completo (coluna 1)
            const tdNomeCompleto = document.createElement('td');
            tdNomeCompleto.textContent = row.nome_completo;
            tr.appendChild(tdNomeCompleto);

            // Nome Exibicao (coluna 2)
            const tdNomeExibicao = document.createElement('td');
            tdNomeExibicao.textContent = row.nome_exibicao || '';
            tr.appendChild(tdNomeExibicao);

            // Ações (coluna 3)
            const tdActions = document.createElement('td');

            // Botão Editar
            const btnEdit = document.createElement('button');
            btnEdit.classList.add('btn-edit');
            btnEdit.dataset.id = row.id_professor;
            btnEdit.innerHTML = `
                <span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
                <span class="text">Editar</span>`;
            tdActions.appendChild(btnEdit);

            // Botão Deletar
            const btnDelete = document.createElement('button');
            btnDelete.classList.add('btn-delete');
            btnDelete.dataset.id = row.id_professor;
            btnDelete.innerHTML = `
                <span class="icon"><i class="fa-solid fa-trash"></i></span>
                <span class="text">Deletar</span>`;
            tdActions.appendChild(btnDelete);

            // Botão Imprimir (Individual)
            const btnPrint = document.createElement('button');
            btnPrint.classList.add('btn-print');
            btnPrint.dataset.id = row.id_professor;
            btnPrint.innerHTML = `
                <span class="icon"><i class="fa-solid fa-print"></i></span>
                <span class="text">Imprimir</span>`;
            tdActions.appendChild(btnPrint);

            // Botão Vincular Disciplinas
            const btnVincularDisc = document.createElement('button');
            btnVincularDisc.classList.add('btn-vincular-disciplina');
            btnVincularDisc.dataset.id = row.id_professor;
            btnVincularDisc.innerHTML = `
                <span class="icon"><i class="fa-solid fa-book"></i></span>
                <span class="text">Disciplinas</span>`;
            tdActions.appendChild(btnVincularDisc);

            // Botão Restrições
            const btnRestricoes = document.createElement('button');
            btnRestricoes.classList.add('btn-restricoes');
            btnRestricoes.dataset.id = row.id_professor;
            btnRestricoes.innerHTML = `
                <span class="icon"><i class="fa-solid fa-ban"></i></span>
                <span class="text">Restrições</span>`;
            tdActions.appendChild(btnRestricoes);

            // Botão Turnos
            const btnTurno = document.createElement('button');
            btnTurno.classList.add('btn-turno');
            btnTurno.dataset.id = row.id_professor;
            btnTurno.innerHTML = `
                <span class="icon"><i class="fa-solid fa-clock"></i></span>
                <span class="text">Turnos</span>`;
            tdActions.appendChild(btnTurno);

            // Botão Vincular Turmas
            const btnVincularDiscTurma = document.createElement('button');
            btnVincularDiscTurma.classList.add('btn-vincular-disciplina-turma');
            btnVincularDiscTurma.dataset.id = row.id_professor;
            btnVincularDiscTurma.innerHTML = `
                <span class="icon"><i class="fa-solid fa-chalkboard"></i></span>
                <span class="text">Turmas</span>`;
            tdActions.appendChild(btnVincularDiscTurma);

            tr.appendChild(tdActions);
            professorTableBody.appendChild(tr);
        });
    }

    /* ============================================================
       SORT nas colunas Nome Completo e Nome Exibição
    ============================================================ */
    const sortNomeAsc = document.getElementById('sort-nome-asc');
    const sortNomeDesc = document.getElementById('sort-nome-desc');
    const sortNomeExibicaoAsc = document.getElementById('sort-nome-exibicao-asc');
    const sortNomeExibicaoDesc = document.getElementById('sort-nome-exibicao-desc');

    function sortTableByNome(asc = true) {
        // Nome Completo está na coluna 1 (td:nth-child(1))
        const rows = Array.from(professorTableBody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const valA = a.querySelector('td:nth-child(1)').textContent.toLowerCase();
            const valB = b.querySelector('td:nth-child(1)').textContent.toLowerCase();
            return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
        });
        professorTableBody.innerHTML = '';
        rows.forEach(row => professorTableBody.appendChild(row));
    }
    function sortTableByNomeExibicao(asc = true) {
        // Nome de Exibição está na coluna 2 (td:nth-child(2))
        const rows = Array.from(professorTableBody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const valA = a.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const valB = b.querySelector('td:nth-child(2)').textContent.toLowerCase();
            return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
        });
        professorTableBody.innerHTML = '';
        rows.forEach(row => professorTableBody.appendChild(row));
    }

    if (sortNomeAsc) {
        sortNomeAsc.addEventListener('click', () => sortTableByNome(true));
    }
    if (sortNomeDesc) {
        sortNomeDesc.addEventListener('click', () => sortTableByNome(false));
    }
    if (sortNomeExibicaoAsc) {
        sortNomeExibicaoAsc.addEventListener('click', () => sortTableByNomeExibicao(true));
    }
    if (sortNomeExibicaoDesc) {
        sortNomeExibicaoDesc.addEventListener('click', () => sortTableByNomeExibicao(false));
    }

    /* ============================================================
       3) ABRIR/FECHAR MODAL DE PROFESSOR (cadastro/edição)
    ============================================================ */
    function openModal() {
        if (modalProfessor) {
            modalProfessor.style.display = 'block';
            modalProfessor.classList.remove('fade-out');
            modalProfessor.classList.add('fade-in');
            const content = modalProfessor.querySelector('.modal-content');
            if (content) {
                content.classList.remove('slide-up');
                content.classList.add('slide-down');
            }
            if (!isEditMode) {
                clearForm();
                const titleElem = document.getElementById('modal-title');
                if (titleElem) titleElem.innerText = 'Adicionar Professor(a)';
                if (saveBtn)    saveBtn.innerText = 'Salvar';
            }
        }
    }

    function closeModal() {
        if (modalProfessor) {
            const content = modalProfessor.querySelector('.modal-content');
            if (content) {
                content.classList.remove('slide-down');
                content.classList.add('slide-up');
            }
            modalProfessor.classList.remove('fade-in');
            modalProfessor.classList.add('fade-out');
            setTimeout(() => {
                modalProfessor.style.display = 'none';
                if (content) {
                    content.classList.remove('slide-up');
                }
                modalProfessor.classList.remove('fade-out');
                isEditMode = false;
                currentEditId = null;
            }, 300);
        }
    }

    function clearForm() {
        if (inputProfessorId)   inputProfessorId.value   = '';
        if (inputNomeCompleto)  inputNomeCompleto.value  = '';
        if (inputNomeExibicao)  inputNomeExibicao.value  = '';
        if (inputTelefone)      inputTelefone.value      = '';
        if (radioSexoMasc)      radioSexoMasc.checked    = true;
        if (radioSexoFem)       radioSexoFem.checked     = false;
        if (radioSexoOutro)     radioSexoOutro.checked   = false;
        if (inputLimiteAulas)   inputLimiteAulas.value   = '';
    }

    if (btnAdd) {
        btnAdd.addEventListener('click', () => {
            isEditMode = false;
            openModal();
        });
    }
    closeModalElements.forEach(el => {
        if (el) el.addEventListener('click', closeModal);
    });
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            if (!isEditMode) {
                clearForm();
            }
            closeModal();
        });
    }

    /* ============================================================
       4) SALVAR (INSERT/UPDATE)
    ============================================================ */
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            const id            = inputProfessorId ? inputProfessorId.value : '';
            const nomeCompleto  = inputNomeCompleto ? inputNomeCompleto.value.trim() : '';
            const nomeExibicao  = inputNomeExibicao ? inputNomeExibicao.value.trim() : '';
            const telefone      = inputTelefone ? inputTelefone.value.trim() : '';

            if (!nomeCompleto || !nomeExibicao || !telefone) {
                alert('Preencha todos os campos.');
                return;
            }
            if (nomeCompleto.length > 100) {
                alert('O nome completo não pode ultrapassar 100 caracteres.');
                return;
            }

            let sexoSelecionado = 'Masculino';
            if (radioSexoFem && radioSexoFem.checked)   sexoSelecionado = 'Feminino';
            if (radioSexoOutro && radioSexoOutro.checked) sexoSelecionado = 'Outro';

            let limite = inputLimiteAulas ? inputLimiteAulas.value.trim() : '0';
            if (!limite) limite = '0';
            const num = parseInt(limite, 10);
            if (isNaN(num) || num < 0 || num > 99) {
                alert('O limite de aulas deve ser um número entre 0 e 99.');
                return;
            }

            const data = new URLSearchParams({
                id_professor:   id,
                nome_completo:  nomeCompleto,
                nome_exibicao:  nomeExibicao,
                telefone:       telefone,
                sexo:           sexoSelecionado,
                limite_aulas:   num
            });

            if (isEditMode) {
                // UPDATE
                fetch('/horarios/app/controllers/professor/updateProfessor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: data
                })
                .then(r => r.json())
                .then(response => {
                    alert(response.message);
                    if (response.status === 'success') {
                        closeModal();
                        fetchProfessores();
                    }
                })
                .catch(err => console.error(err));
            } else {
                // INSERT
                data.delete('id_professor'); // não precisa de ID no insert
                fetch('/horarios/app/controllers/professor/insertProfessor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: data
                })
                .then(r => r.json())
                .then(response => {
                    if (response.status === 'success') {
                        fetchProfessores();
                        const resp = confirm('Professor inserido com sucesso! Deseja inserir outro professor?');
                        if (resp) {
                            clearForm();
                        } else {
                            closeModal();
                        }
                    } else {
                        alert(response.message);
                    }
                })
                .catch(err => console.error(err));
            }
        });
    }

    /* ============================================================
       5) AÇÕES NA TABELA – Delegação de eventos
    ============================================================ */
    if (professorTableBody) {
        professorTableBody.addEventListener('click', e => {
            const btn = e.target.closest('button');
            if (!btn) return;
            const id = btn.dataset.id;

            if (btn.classList.contains('btn-edit')) {
                // EDITAR
                isEditMode    = true;
                currentEditId = id;
                fetch('/horarios/app/controllers/professor/listProfessor.php')
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const professor = data.data.find(item => item.id_professor == currentEditId);
                            if (professor) {
                                if (inputProfessorId)   inputProfessorId.value   = professor.id_professor;
                                if (inputNomeCompleto)  inputNomeCompleto.value  = professor.nome_completo || '';
                                if (inputNomeExibicao)  inputNomeExibicao.value  = professor.nome_exibicao || '';
                                if (inputTelefone)      inputTelefone.value      = professor.telefone || '';

                                if (professor.sexo === 'Feminino') {
                                    if (radioSexoFem)   radioSexoFem.checked   = true;
                                } else if (professor.sexo === 'Outro') {
                                    if (radioSexoOutro) radioSexoOutro.checked = true;
                                } else {
                                    if (radioSexoMasc)  radioSexoMasc.checked  = true;
                                }

                                let limitVal = parseInt(professor.limite_aulas_fixa_semana, 10);
                                if (isNaN(limitVal)) limitVal = 0;
                                if (inputLimiteAulas) inputLimiteAulas.value = limitVal.toString();

                                const titleElem = document.getElementById('modal-title');
                                if (titleElem) titleElem.innerText = 'Editar Professor(a)';
                                if (saveBtn)   saveBtn.innerText    = 'Alterar';
                                openModal();
                            }
                        }
                    });
            } else if (btn.classList.contains('btn-delete')) {
                // EXCLUIR
                currentEditId = id;
                openDeleteModal();
            } else if (btn.classList.contains('btn-print')) {
                // IMPRIMIR INDIVIDUAL
                currentPrintProfessorId = id;
                const tr   = btn.closest('tr');
                const nome = tr.querySelector('td:nth-child(1)').textContent.trim();
                const inputSelected = document.getElementById('selected-professor');
                if (inputSelected) {
                    inputSelected.value = nome;
                }
                openPrintProfessorModal();
            } else if (btn.classList.contains('btn-vincular-disciplina')) {
                // VINCULAR DISCIPLINAS
                const tr = btn.closest('tr');
                const professorName = tr.querySelector('td:nth-child(1)').textContent.trim();
                const selectProfDisc = document.getElementById('select-professor-disciplina');
                if (selectProfDisc) selectProfDisc.value = id;
                const profNomeDisc = document.getElementById('prof-nome-disciplina');
                if (profNomeDisc) profNomeDisc.value = professorName;
                openProfessorDisciplinaModal(id);
            } else if (btn.classList.contains('btn-restricoes')) {
                // RESTRIÇÕES
                const tr = btn.closest('tr');
                const professorName = tr.querySelector('td:nth-child(1)').textContent.trim();
                openProfessorRestricoesModal(id, professorName);
            } else if (btn.classList.contains('btn-turno')) {
                // TURNOS
                const tr = btn.closest('tr');
                const professorName = tr.querySelector('td:nth-child(1)').textContent.trim();
                const profNomeTurno = document.getElementById('prof-nome-turno');
                if (profNomeTurno) profNomeTurno.value = professorName;
                const selectProfTurno = document.getElementById('select-professor-turno');
                if (selectProfTurno) selectProfTurno.value = id;
                openProfessorTurnoModal(id);
            } else if (btn.classList.contains('btn-vincular-disciplina-turma')) {
                // VINCULAR DISCIPLINA-TURMA
                const tr = btn.closest('tr');
                const professorName = tr.querySelector('td:nth-child(1)').textContent.trim();
                openProfessorDisciplinaTurmaModal(id, professorName);
            }
        });
    }

    /* ============================================================
       6) MODAL DE EXCLUSÃO (ANIMADO)
    ============================================================ */
    function openDeleteModal() {
        if (modalDelete) {
            modalDelete.style.display = 'block';
            modalDelete.classList.remove('fade-out');
            modalDelete.classList.add('fade-in');
            const content = modalDelete.querySelector('.modal-content');
            if (content) {
                content.classList.remove('slide-up');
                content.classList.add('slide-down');
            }
        }
    }
    function closeDeleteModal() {
        if (modalDelete) {
            const content = modalDelete.querySelector('.modal-content');
            if (content) {
                content.classList.remove('slide-down');
                content.classList.add('slide-up');
            }
            modalDelete.classList.remove('fade-in');
            modalDelete.classList.add('fade-out');
            setTimeout(() => {
                modalDelete.style.display = 'none';
                if (content) content.classList.remove('slide-up');
                modalDelete.classList.remove('fade-out');
            }, 300);
        }
    }
    if (closeDeleteModalBtn) {
        closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
    }
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    }
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', () => {
            fetch('/horarios/app/controllers/professor/deleteProfessor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id: currentEditId })
            })
            .then(r => r.json())
            .then(response => {
                alert(response.message);
                if (response.status === 'success') {
                    const row = document.querySelector(`tr[data-id="${currentEditId}"]`);
                    if (row) row.remove();
                    if (professorTableBody.children.length === 0) {
                        noDataMessage.style.display = 'block';
                    }
                }
                closeDeleteModal();
            })
            .catch(err => console.error(err));
        });
    }

    /* ============================================================
       7) MODAL DE IMPRESSÃO (INDIVIDUAL) – ABRIR E FECHAR
    ============================================================ */
    function openPrintProfessorModal() {
        const chkDisciplinas = document.getElementById('chk-prof-disciplinas');
        const chkRestricoes  = document.getElementById('chk-prof-restricoes');
        const chkTurnos      = document.getElementById('chk-prof-turnos');
        const chkTurmas      = document.getElementById('chk-prof-turmas');
        const chkHorarios    = document.getElementById('chk-prof-horarios'); 

        if (chkDisciplinas) chkDisciplinas.checked = false;
        if (chkRestricoes)  chkRestricoes.checked  = false;
        if (chkTurnos)      chkTurnos.checked      = false;
        if (chkTurmas)      chkTurmas.checked      = false;
        if (chkHorarios)    chkHorarios.checked    = false;

        const selAno = document.getElementById('select-ano-letivo-print');
        if (selAno) {
            selAno.innerHTML = '<option value="">-- Selecione --</option>';
            carregarAnosLetivos();
        }

        if (modalPrint) {
            modalPrint.style.display = 'block';
            modalPrint.classList.remove('fade-out');
            modalPrint.classList.add('fade-in');
            const content = modalPrint.querySelector('.modal-content');
            if (content) {
                content.classList.remove('slide-up');
                content.classList.add('slide-down');
            }
        }
    }

    if (closePrintProfessorBtn) {
        closePrintProfessorBtn.addEventListener('click', () => {
            if (modalPrint) {
                const content = modalPrint.querySelector('.modal-content');
                if (content) {
                    content.classList.remove('slide-down');
                    content.classList.add('slide-up');
                }
                modalPrint.classList.remove('fade-in');
                modalPrint.classList.add('fade-out');
                setTimeout(() => {
                    modalPrint.style.display = 'none';
                    if (content) content.classList.remove('slide-up');
                    modalPrint.classList.remove('fade-out');
                }, 300);
            }
        });
    }

    if (btnCancelarProfessor) {
        btnCancelarProfessor.addEventListener('click', () => {
            if (modalPrint) {
                const content = modalPrint.querySelector('.modal-content');
                if (content) {
                    content.classList.remove('slide-down');
                    content.classList.add('slide-up');
                }
                modalPrint.classList.remove('fade-in');
                modalPrint.classList.add('fade-out');
                setTimeout(() => {
                    modalPrint.style.display = 'none';
                    if (content) content.classList.remove('slide-up');
                    modalPrint.classList.remove('fade-out');
                }, 300);
            }
        });
    }

    if (btnImprimirProfessor) {
        btnImprimirProfessor.addEventListener('click', () => {
            let url = '/horarios/app/views/professor.php?id_professor=' + currentPrintProfessorId;

            const selAno = document.getElementById('select-ano-letivo-print');
            if (selAno && selAno.value) {
                url += '&id_ano=' + encodeURIComponent(selAno.value);
            }

            if (document.getElementById('chk-prof-disciplinas')?.checked) {
                url += '&disciplina=1';
            }
            if (document.getElementById('chk-prof-restricoes')?.checked) {
                url += '&restricoes=1';
            }
            if (document.getElementById('chk-prof-turnos')?.checked) {
                url += '&turnos=1';
            }
            if (document.getElementById('chk-prof-turmas')?.checked) {
                url += '&turmas=1';
            }
            if (document.getElementById('chk-prof-horarios')?.checked) {
                url += '&horarios=1';
            }

            window.open(url, '_blank');
            if (modalPrint) {
                modalPrint.style.display = 'none';
            }
        });
    }

    /* ============================================================
       8) MODAL DE IMPRESSÃO GERAL
    ============================================================ */
    function openModalPrintGeral() {
        if (!modalPrintGeral) return;

        const chkDisciplinas = document.getElementById('chk-geral-disciplinas');
        const chkRestricoes  = document.getElementById('chk-geral-restricoes');
        const chkTurnos      = document.getElementById('chk-geral-turnos');
        const chkTurmas      = document.getElementById('chk-geral-turmas');
        const chkHorarios    = document.getElementById('chk-geral-horarios');

        if (chkDisciplinas) chkDisciplinas.checked = false;
        if (chkRestricoes)  chkRestricoes.checked  = false;
        if (chkTurnos)      chkTurnos.checked      = false;
        if (chkTurmas)      chkTurmas.checked      = false;
        if (chkHorarios)    chkHorarios.checked    = false;

        const selGeral = document.getElementById('select-ano-letivo-print-geral');
        if (selGeral) {
            selGeral.innerHTML = '<option value="">-- Selecione --</option>';
        }
        carregarAnosLetivos();

        modalPrintGeral.style.display = 'block';
        modalPrintGeral.classList.remove('fade-out');
        modalPrintGeral.classList.add('fade-in');
        const content = modalPrintGeral.querySelector('.modal-content');
        if (content) {
            content.classList.remove('slide-up');
            content.classList.add('slide-down');
        }
    }

    function closeModalPrintGeral() {
        if (!modalPrintGeral) return;
        const content = modalPrintGeral.querySelector('.modal-content');
        if (content) {
            content.classList.remove('slide-down');
            content.classList.add('slide-up');
        }
        modalPrintGeral.classList.remove('fade-in');
        modalPrintGeral.classList.add('fade-out');
        setTimeout(() => {
            modalPrintGeral.style.display = 'none';
            if (content) content.classList.remove('slide-up');
            modalPrintGeral.classList.remove('fade-out');
        }, 300);
    }

    if (btnOpenPrintGeral) {
        btnOpenPrintGeral.addEventListener('click', () => {
            openModalPrintGeral();
        });
    }
    if (closePrintGeral) {
        closePrintGeral.addEventListener('click', () => {
            closeModalPrintGeral();
        });
    }
    if (btnCancelarGeral) {
        btnCancelarGeral.addEventListener('click', () => {
            closeModalPrintGeral();
        });
    }
    if (btnImprimirGeral) {
        btnImprimirGeral.addEventListener('click', () => {
            let url = '/horarios/app/views/professor-geral.php?';

            const selAnoGeral = document.getElementById('select-ano-letivo-print-geral');
            if (selAnoGeral && selAnoGeral.value) {
                url += 'id_ano=' + encodeURIComponent(selAnoGeral.value);
            }

            if (document.getElementById('chk-geral-disciplinas')?.checked) {
                url += '&disciplina=1';
            }
            if (document.getElementById('chk-geral-restricoes')?.checked) {
                url += '&restricoes=1';
            }
            if (document.getElementById('chk-geral-turnos')?.checked) {
                url += '&turnos=1';
            }
            if (document.getElementById('chk-geral-turmas')?.checked) {
                url += '&turmas=1';
            }
            if (document.getElementById('chk-geral-horarios')?.checked) {
                url += '&horarios=1';
            }

            window.open(url, '_blank');
            closeModalPrintGeral();
        });
    }

    /* ============================================================
       9) PESQUISA
    ============================================================ */
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = professorTableBody.querySelectorAll('tr');
            let count = 0;
            rows.forEach(tr => {
                const nome      = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const exibicao  = tr.querySelector('td:nth-child(2)').textContent.toLowerCase();
                if (nome.includes(searchValue) || exibicao.includes(searchValue)) {
                    tr.style.display = '';
                    count++;
                } else {
                    tr.style.display = 'none';
                }
            });
            noDataMessage.style.display = count === 0 ? 'block' : 'none';
        });
    }

    /* ============================================================
       10) FECHAR MODAIS AO CLICAR FORA (opcional)
    ============================================================ */
    window.addEventListener('click', e => {
        if (e.target === modalProfessor) {
            // closeModal(); // se quiser fechar ao clicar fora
        }
        if (e.target === modalDelete) {
            // closeDeleteModal();
        }
        if (e.target === modalPrint) {
            // closePrintProfessorModal();
        }
        if (e.target === modalPrintGeral) {
            // closeModalPrintGeral();
        }
    });

    /* ============================================================
       11) INICIALIZA
    ============================================================ */
    fetchProfessores();
});
