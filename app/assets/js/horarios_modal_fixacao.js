// app/js/horarios_modal_fixacao.js

// ========================================
// MODAL DE FIXAÇÃO DE DISCIPLINA v3.0
// COM DIAS DINÂMICOS DO BANCO DE DADOS
// ========================================

// Variável global para armazenar os dias disponíveis
let diasDisponiveis = [];

// ========================================
// CRIAR MODAL DE FIXAÇÃO (HTML)
// ========================================
function criarModalFixacao() {
    // Remove modal antigo se existir
    const modalAntigo = document.getElementById('modal-fixacao-disciplina');
    if (modalAntigo) modalAntigo.remove();

    const modalHTML = `
        <div id="modal-fixacao-disciplina" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h2>⚙️ Configurar Geração de Horários</h2>
                    <span class="close-modal-fixacao" style="cursor: pointer; font-size: 28px; font-weight: bold;">&times;</span>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="checkFixarDisciplina" style="width: 20px; height: 20px; cursor: pointer;">
                            <span style="font-weight: 600; font-size: 15px;">
                                Fixar disciplina em horário específico
                            </span>
                        </label>
                        <small style="color: #666; display: block; margin-top: 5px; margin-left: 30px;">
                            Marque para alocar uma disciplina no mesmo horário em todas as turmas
                        </small>
                    </div>
                    
                    <div id="campos-fixacao" style="display: none; margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #6358F8;">
                        <div class="form-group">
                            <label for="selectDisciplinaFixa" style="font-weight: 600; display: block; margin-bottom: 8px;">
                                📚 Disciplina:
                            </label>
                            <select id="selectDisciplinaFixa" class="form-control" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
                                <option value="">-- Carregando... --</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-top: 15px;">
                            <label for="selectDiaFixo" style="font-weight: 600; display: block; margin-bottom: 8px;">
                                📅 Dia da semana:
                            </label>
                            <select id="selectDiaFixo" class="form-control" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
                                <option value="">-- Carregando dias... --</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-top: 15px;">
                            <label for="selectAulaFixa" style="font-weight: 600; display: block; margin-bottom: 8px;">
                                ⏰ Período:
                            </label>
                            <select id="selectAulaFixa" class="form-control" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
                                <option value="">-- Selecione o dia primeiro --</option>
                            </select>
                        </div>
                        
                        <div id="resumo-fixacao" style="margin-top: 20px; padding: 15px; background: white; border-radius: 6px; border: 1px solid #ddd; display: none;">
                            <strong style="color: #6358F8;">📌 Resumo:</strong>
                            <p id="texto-resumo" style="margin: 10px 0 0 0; color: #333;"></p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" id="btnCancelarFixacao" style="background-color: #FC3B56; color: white;">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                    <button type="button" id="btnConfirmarGeracao" style="background-color: #6358F8; color: white;">
                        <i class="fa-solid fa-check"></i> Gerar Horários
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// ========================================
// 🆕 CRIAR OVERLAY DE LOADING
// ========================================
function criarLoadingOverlay() {
    const loadingHTML = `
        <div id="loading-overlay" style="
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 99999;
            justify-content: center;
            align-items: center;
        ">
            <div style="
                background: white;
                padding: 40px 60px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                text-align: center;
            ">
                <div class="spinner" style="
                    border: 6px solid #f3f3f3;
                    border-top: 6px solid #6358F8;
                    border-radius: 50%;
                    width: 60px;
                    height: 60px;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 20px auto;
                "></div>
                <h3 style="margin: 0; color: #333; font-size: 18px;">
                    ⏳ Gerando horários...
                </h3>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                    Por favor, aguarde. Isso pode levar alguns segundos.
                </p>
            </div>
        </div>
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
    
    // Remove overlay antigo se existir
    const overlayAntigo = document.getElementById('loading-overlay');
    if (overlayAntigo) overlayAntigo.remove();
    
    document.body.insertAdjacentHTML('beforeend', loadingHTML);
}

function mostrarLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        criarLoadingOverlay();
    }
    document.getElementById('loading-overlay').style.display = 'flex';
}

function esconderLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// ========================================
// CARREGAR DISCIPLINAS VIA API
// ========================================
async function carregarDisciplinasParaFixacao(idNivelEnsino) {
    const select = document.getElementById('selectDisciplinaFixa');
    
    try {
        const response = await fetch(`/horarios/app/controllers/horarios/listarDisciplinas.php?id_nivel_ensino=${idNivelEnsino}`);
        const data = await response.json();
        
        if (data.status === 'success' && data.disciplinas) {
            select.innerHTML = '<option value="">-- Selecione a disciplina --</option>';
            data.disciplinas.forEach(disc => {
                const option = document.createElement('option');
                option.value = disc.id_disciplina;
                option.textContent = `${disc.nome_disciplina} (${disc.sigla_disciplina})`;
                select.appendChild(option);
            });
        } else {
            select.innerHTML = '<option value="">❌ Erro ao carregar disciplinas</option>';
        }
    } catch (error) {
        console.error('Erro ao carregar disciplinas:', error);
        select.innerHTML = '<option value="">❌ Erro ao carregar</option>';
    }
}

// ========================================
// 🆕 CARREGAR DIAS DA SEMANA VIA API
// ========================================
// ✅ Mantém compatibilidade: continua enviando id_nivel_ensino + id_ano_letivo
// ✅ Acrescenta id_turno sem quebrar o PHP antigo
async function carregarDiasDisponiveis(idNivelEnsino, idAnoLetivo, idTurno) {
    const selectDia = document.getElementById('selectDiaFixo');
    const selectAula = document.getElementById('selectAulaFixa');

    try {
        // validação mínima
        idNivelEnsino = String(idNivelEnsino || '').trim();
        idAnoLetivo   = String(idAnoLetivo || '').trim();
        idTurno       = String(idTurno || '').trim();

        if (!idNivelEnsino || !idAnoLetivo) {
            console.error('Erro: Parâmetros inválidos', { idNivelEnsino, idAnoLetivo, idTurno });
            throw new Error('Parâmetros inválidos');
        }

        selectDia.innerHTML = '<option value="">-- Carregando... --</option>';
        selectAula.innerHTML = '<option value="">-- Selecione o dia primeiro --</option>';

        // ✅ chamada original + ✅ acrescimo id_turno
        const url =
            `/horarios/app/controllers/horarios/listarDiasTurno.php` +
            `?id_nivel_ensino=${encodeURIComponent(idNivelEnsino)}` +
            `&id_ano_letivo=${encodeURIComponent(idAnoLetivo)}` +
            (idTurno ? `&id_turno=${encodeURIComponent(idTurno)}` : '');

        const response = await fetch(url);
        const data = await response.json();

        console.log('📅 Dias carregados:', data);

        if (data.status === 'success' && Array.isArray(data.dias)) {
            diasDisponiveis = data.dias;

            selectDia.innerHTML = '<option value="">-- Selecione o dia --</option>';
            data.dias.forEach(dia => {
                const option = document.createElement('option');
                option.value = dia.dia_semana;
                option.textContent = `${dia.nome_exibicao} (${dia.aulas_no_dia} aulas)`;
                option.dataset.aulas = dia.aulas_no_dia;
                selectDia.appendChild(option);
            });

            selectAula.innerHTML = '<option value="">-- Selecione o dia primeiro --</option>';
        } else {
            selectDia.innerHTML = '<option value="">❌ Erro ao carregar dias</option>';
            console.error('Erro:', data.message || data);
        }

    } catch (error) {
        console.error('Erro ao carregar dias:', error);
        selectDia.innerHTML = '<option value="">❌ Erro ao carregar</option>';
    }
}

// ========================================
// 🆕 ATUALIZAR AULAS BASEADO NO DIA
// ========================================
function atualizarAulasDisponiveis() {
    const selectDia = document.getElementById('selectDiaFixo');
    const selectAula = document.getElementById('selectAulaFixa');
    
    const diaSelecionado = selectDia.value;
    
    if (!diaSelecionado) {
        selectAula.innerHTML = '<option value="">-- Selecione o dia primeiro --</option>';
        return;
    }
    
    // Encontra a quantidade de aulas do dia selecionado
    const diaInfo = diasDisponiveis.find(d => d.dia_semana === diaSelecionado);
    
    if (!diaInfo) {
        selectAula.innerHTML = '<option value="">❌ Dia inválido</option>';
        return;
    }
    
    const qtdAulas = diaInfo.aulas_no_dia;
    
    selectAula.innerHTML = '<option value="">-- Selecione a aula --</option>';
    for (let i = 1; i <= qtdAulas; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = `${i}ª aula`;
        selectAula.appendChild(option);
    }
    
    console.log(`📚 Dia ${diaSelecionado}: ${qtdAulas} aulas disponíveis`);
}

// ========================================
// EVENTOS DO MODAL
// ========================================
function inicializarEventosModal() {
    const checkbox = document.getElementById('checkFixarDisciplina');
    const camposFixacao = document.getElementById('campos-fixacao');
    const selectDisciplina = document.getElementById('selectDisciplinaFixa');
    const selectDia = document.getElementById('selectDiaFixo');
    const selectAula = document.getElementById('selectAulaFixa');
    const resumo = document.getElementById('resumo-fixacao');
    const textoResumo = document.getElementById('texto-resumo');

    // Toggle campos de fixação
    checkbox.addEventListener('change', function() {
        camposFixacao.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            resumo.style.display = 'none';
        }
    });

    // 🆕 Quando mudar o dia, atualiza as aulas disponíveis
    selectDia.addEventListener('change', function() {
        atualizarAulasDisponiveis();
        atualizarResumo();
    });

    // Atualizar resumo quando selecionar valores
    function atualizarResumo() {
        if (!checkbox.checked) return;

        const disciplinaNome = selectDisciplina.options[selectDisciplina.selectedIndex]?.text || '';
        const dia = selectDia.value;
        const aula = selectAula.options[selectAula.selectedIndex]?.text || '';

        if (selectDisciplina.value && dia && selectAula.value) {
            const diaNome = selectDia.options[selectDia.selectedIndex].text;
            textoResumo.innerHTML = `A disciplina <strong>${disciplinaNome}</strong> será alocada no horário <strong>${diaNome.split(' (')[0]} - ${aula}</strong> em <strong>TODAS as turmas</strong>.`;
            resumo.style.display = 'block';
        } else {
            resumo.style.display = 'none';
        }
    }

    selectDisciplina.addEventListener('change', atualizarResumo);
    selectAula.addEventListener('change', atualizarResumo);
}

// ========================================
// ABRIR/FECHAR MODAL
// ========================================
function abrirModalFixacao(idAnoLetivo, idNivelEnsino, idTurno, callback) {
    if (!document.getElementById('modal-fixacao-disciplina')) criarModalFixacao();

    const modal = document.getElementById('modal-fixacao-disciplina');
    const content = modal.querySelector('.modal-content');

    // compat: abrirModalFixacao(idAno, idNivel, callback)
    if (typeof idTurno === 'function' && callback === undefined) {
        callback = idTurno;
        idTurno = document.getElementById('selectTurno')?.value || null;
    }

    if (!idTurno) idTurno = document.getElementById('selectTurno')?.value || null;
    idTurno = String(idTurno || '').trim();
    if (!idTurno) { alert('⚠️ Selecione o Turno antes de continuar.'); return; }

    carregarDisciplinasParaFixacao(idNivelEnsino);
    carregarDiasDisponiveis(idNivelEnsino, idAnoLetivo, idTurno);

    inicializarEventosModal();

    modal.style.display = 'block';
    modal.classList.remove('fade-out');
    modal.classList.add('fade-in');
    content.classList.remove('slide-up');
    content.classList.add('slide-down');

    // Botão cancelar
    document.getElementById('btnCancelarFixacao').onclick = function() {
        fecharModalFixacao();
    };

    // Botão X (fechar)
    document.querySelector('.close-modal-fixacao').onclick = function() {
        fecharModalFixacao();
    };

    // Clicar fora do modal fecha
    modal.onclick = function(e) {
        if (e.target === modal) {
            fecharModalFixacao();
        }
    };

    // Botão confirmar geração
    document.getElementById('btnConfirmarGeracao').onclick = function() {
        const checkbox = document.getElementById('checkFixarDisciplina');
        let dadosFixacao = null;

        if (checkbox.checked) {
            const disciplinaId = document.getElementById('selectDisciplinaFixa').value;
            const dia = document.getElementById('selectDiaFixo').value;
            const aula = document.getElementById('selectAulaFixa').value;

            if (!disciplinaId || !dia || !aula) {
                alert('⚠️ Por favor, preencha todos os campos da fixação ou desmarque a opção.');
                return;
            }

            dadosFixacao = {
                id_disciplina: disciplinaId,
                dia_semana: dia,
                numero_aula: parseInt(aula, 10)
            };

            console.log('🔒 DADOS DE FIXAÇÃO:', dadosFixacao);
        }

        fecharModalFixacao();
        callback(dadosFixacao);
    };
}


function fecharModalFixacao() {
    const modal = document.getElementById('modal-fixacao-disciplina');
    const content = modal.querySelector('.modal-content');
    
    // Animações de saída
    content.classList.remove('slide-down');
    content.classList.add('slide-up');
    modal.classList.remove('fade-in');
    modal.classList.add('fade-out');

    // Espera a animação e esconde
    setTimeout(() => {
        modal.style.display = 'none';
        content.classList.remove('slide-up');
        modal.classList.remove('fade-out');
    }, 300);
}

// ========================================
// INTEGRAÇÃO COM SISTEMA EXISTENTE
// ========================================
function iniciarGeracaoComModal(idAnoLetivo, idNivelEnsino, idTurno) {
    // se não veio idTurno, pega do select
    if (!idTurno) idTurno = document.getElementById('selectTurno')?.value || null;

    abrirModalFixacao(idAnoLetivo, idNivelEnsino, idTurno, function(dadosFixacao) {
        if (!idTurno) idTurno = document.getElementById('selectTurno')?.value || null;
        idTurno = String(idTurno || '').trim();

        /*if (!idTurno) {
            alert('⚠️ Selecione o Turno antes de gerar.');
            return;
        }*/

        idTurno = parseInt(idTurno, 10);
        if (!Number.isInteger(idTurno) || idTurno <= 0) {
            alert('⚠️ Selecione o Turno antes de continuar.');
            return;
        }

        gerarHorariosAutomaticosComFixacao(idAnoLetivo, idNivelEnsino, idTurno, dadosFixacao);
    });
}




// ========================================
// 🆕 FUNÇÃO DE GERAÇÃO COM LOADING
// ========================================
async function gerarHorariosAutomaticosComFixacao(idAnoLetivo, idNivelEnsino, idTurno, dadosFixacao) {
    try {
        mostrarLoading();
        
        console.log('========================================');
        console.log('📤 ENVIANDO DADOS PARA GERAÇÃO');
        console.log('========================================');
        console.log('Ano Letivo:', idAnoLetivo);
        console.log('Nível Ensino:', idNivelEnsino);
        console.log('Dados de Fixação:', dadosFixacao);
        
        // Prepara os dados para envio
        const formData = new URLSearchParams();
        formData.append('id_ano_letivo', idAnoLetivo);
        formData.append('id_nivel_ensino', idNivelEnsino);
        formData.append('id_turno', idTurno); // ✅ ACRÉSCIMO

        // Adiciona dados de fixação se existir
        if (dadosFixacao) {
            formData.append('fixar_disciplina', 'true');
            formData.append('disciplina_fixa_id', dadosFixacao.id_disciplina);
            formData.append('disciplina_fixa_dia', dadosFixacao.dia_semana);
            formData.append('disciplina_fixa_aula', dadosFixacao.numero_aula);
            
            console.log('✅ Fixação ATIVADA:');
            console.log('  - Disciplina ID:', dadosFixacao.id_disciplina);
            console.log('  - Dia:', dadosFixacao.dia_semana);
            console.log('  - Aula:', dadosFixacao.numero_aula);
        } else {
            console.log('⚪ Fixação NÃO ativada (checkbox desmarcado)');
        }
        
        console.log('');
        console.log('📨 POST Data:', Object.fromEntries(formData));
        console.log('');

        const response = await fetch('/horarios/app/controllers/horarios/gerarHorariosAutomaticos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        const data = await response.json();
        
        // 🆕 ESCONDE LOADING
        esconderLoading();

        if (data.status === 'success') {
            // 🆕 MOSTRA O DIAGNÓSTICO NO CONSOLE
            console.clear();
            console.log('%c========================================', 'color: #4CAF50; font-weight: bold');
            console.log('%c📊 DIAGNÓSTICO DE GERAÇÃO DE HORÁRIOS', 'color: #4CAF50; font-weight: bold; font-size: 16px');
            console.log('%c========================================', 'color: #4CAF50; font-weight: bold');
            console.log('');
            console.log(data.message);
            console.log('');
            console.log('%c========================================', 'color: #4CAF50; font-weight: bold');
            
            // 🆕 CRIA MODAL VISUAL COM O DIAGNÓSTICO
            mostrarDiagnosticoModal(data.message);
            
            // Mensagem simplificada no alert
            alert('✅ Horários gerados com sucesso!\n\nVeja o diagnóstico completo:\n• No CONSOLE (F12)\n• No modal que apareceu');
            
            // 🆕 RECARREGA A PÁGINA AUTOMATICAMENTE
            console.log('🔄 Recarregando página...');
            location.reload();
            
        } else {
            console.error('❌ ERRO:', data.message);
            alert('❌ Erro: ' + (data.message || 'Erro ao gerar horários!'));
        }
    } catch (error) {
        // 🆕 ESCONDE LOADING EM CASO DE ERRO
        esconderLoading();
        
        console.error('❌ ERRO FATAL:', error);
        alert('❌ Ocorreu um erro ao gerar horários automáticos.\n\nDetalhes no console (F12)');
    }
}

// 🆕 FUNÇÃO PARA MOSTRAR MODAL COM DIAGNÓSTICO
function mostrarDiagnosticoModal(mensagem) {
    // Remove modal anterior se existir
    const modalAntigo = document.getElementById('modal-diagnostico');
    if (modalAntigo) modalAntigo.remove();
    
    // Cria o modal usando as classes do sistema
    const modalHTML = `
        <div id="modal-diagnostico" class="modal" style="display: block;">
            <div class="modal-content" style="max-width: 900px; max-height: 80vh; overflow-y: auto;">
                <div class="modal-header">
                    <h2>📊 Diagnóstico Completo</h2>
                    <span class="close-modal-diag" style="cursor: pointer; font-size: 28px; font-weight: bold;">&times;</span>
                </div>
                
                <div class="modal-body">
                    <pre style="
                        background: #f5f5f5;
                        padding: 20px;
                        border-radius: 8px;
                        overflow-x: auto;
                        white-space: pre-wrap;
                        font-family: 'Courier New', monospace;
                        font-size: 13px;
                        line-height: 1.6;
                    ">${mensagem}</pre>
                </div>
                
                <div class="modal-footer">
                    <button id="copiar-diagnostico" style="background-color: #6358F8; color: white;">
                        <i class="fa-solid fa-copy"></i> Copiar
                    </button>
                    <button id="baixar-diagnostico" style="background-color: #81D43A; color: white;">
                        <i class="fa-solid fa-download"></i> Baixar TXT
                    </button>
                    <button id="fechar-diagnostico" style="background-color: #FC3B56; color: white;">
                        <i class="fa-solid fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('modal-diagnostico');
    const content = modal.querySelector('.modal-content');
    
    // Animações
    modal.classList.add('fade-in');
    content.classList.add('slide-down');
    
    // Botão fechar
    function fechar() {
        content.classList.remove('slide-down');
        content.classList.add('slide-up');
        modal.classList.remove('fade-in');
        modal.classList.add('fade-out');
        setTimeout(() => modal.remove(), 300);
    }
    
    document.querySelector('.close-modal-diag').onclick = fechar;
    document.getElementById('fechar-diagnostico').onclick = fechar;
    modal.onclick = (e) => { if (e.target === modal) fechar(); };
    
    // Botão copiar
    document.getElementById('copiar-diagnostico').onclick = () => {
        navigator.clipboard.writeText(mensagem).then(() => {
            alert('✅ Diagnóstico copiado para área de transferência!');
        }).catch(() => {
            alert('❌ Erro ao copiar. Tente selecionar e copiar manualmente.');
        });
    };
    
    // Botão baixar
    document.getElementById('baixar-diagnostico').onclick = () => {
        const blob = new Blob([mensagem], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `diagnostico-horarios-${new Date().toISOString().slice(0,10)}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    };
}

// ========================================
// EXPORTAR PARA USO GLOBAL
// ========================================
window.iniciarGeracaoComModal = iniciarGeracaoComModal;
window.gerarHorariosAutomaticosComFixacao = gerarHorariosAutomaticosComFixacao;

// Cria o loading overlay ao carregar o script
criarLoadingOverlay();

console.log('✅ Modal de Fixação v3.0 (DIAS DINÂMICOS) carregado com sucesso!');