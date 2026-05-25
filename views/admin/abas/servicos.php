<?php
/**
 * views/admin/abas/servicos.php
 * Catálogo de Serviços — grid de cards + modal de edição SPA.
 * Fragmento HTML puro (sem DOCTYPE/head/body).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../models/Servico.php';
require_once __DIR__ . '/../../../models/Profissional.php';
require_once __DIR__ . '/../../../config/Database.php';

AutenticacaoController::exigirAutenticacao('admin');

$servicos      = Servico::listarTodos();
$profissionais = Profissional::listarTodos();

/* Mapa id → dados do profissional para os avatares */
$profMap = [];
foreach ($profissionais as $p) {
    $profMap[$p->getId()] = [
        'apelido' => $p->getApelido(),
        'cor'     => $p->getCorAgenda() ?? '#e91e8c',
        'foto'    => $p->getFotoUrl(),
    ];
}

/* Busca quais profissionais atendem cada serviço */
$pdo = Database::getInstance();
$relMap = []; // servico_id => [profissional_id, ...]
try {
    $stmt = $pdo->query("SELECT servico_id, profissional_id FROM servico_profissional");
    while ($row = $stmt->fetch()) {
        $relMap[(int)$row['servico_id']][] = (int)$row['profissional_id'];
    }
} catch (\Throwable) {
    // Fallback caso a tabela não exista
    foreach ($servicos as $s) {
        foreach ($profissionais as $p) {
            $relMap[(int)$s->getId()][] = (int)$p->getId();
        }
    }
}

// Indicadores de Sumário (KPIs)
$totalServices   = count($servicos);
$ativosServices  = count(array_filter($servicos, fn($s) => $s->isAtivo()));
$inativosServices = $totalServices - $ativosServices;
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800 font-serif" style="font-family:'Playfair Display', serif">Catálogo de Serviços</h2>
        <p class="text-xs text-gray-400">Gerencie os serviços oferecidos e vincule os profissionais capacitados</p>
    </div>
    
    <div class="flex items-center gap-3 flex-wrap">
        <div class="relative min-w-[200px]">
            <input type="text" id="svBusca" oninput="filtrarServicosLocal()" placeholder="Buscar serviço..." class="input-admin text-xs py-1.5 pl-8 pr-3 w-full bg-white shadow-xs border-gray-200 rounded-xl">
            <span class="absolute left-2.5 top-2.5 text-gray-400 text-xs">🔍</span>
        </div>
        
        <select id="svFiltroStatus" onchange="filtrarServicosLocal()" class="input-admin text-xs py-1.5 px-3 bg-white shadow-xs border-gray-200 rounded-xl">
            <option value="todos">Todos os Status</option>
            <option value="ativos">Ativos</option>
            <option value="inativos">Inativos</option>
        </select>

        <button onclick="prepararNovoServico()" class="btn-rosa-sm py-2 px-4 text-xs font-bold rounded-xl shadow-md shadow-pink-100 flex items-center gap-1.5">
            <span>✨</span> Novo Serviço
        </button>
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-gray-100 rounded-xl p-3 shadow-xs border-l-4 border-l-pink-500">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total de Serviços</p>
        <p class="text-xl font-black text-gray-800 mt-0.5"><?=$totalServices?></p>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-3 shadow-xs border-l-4 border-l-emerald-500">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Ativos</p>
        <p class="text-xl font-black text-emerald-600 mt-0.5"><?=$ativosServices?></p>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-3 shadow-xs border-l-4 border-l-rose-400">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Inativos</p>
        <p class="text-xl font-black text-rose-500 mt-0.5"><?=$inativosServices?></p>
    </div>
</div>

<div id="svGridCards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
    <?php if (empty($servicos)): ?>
        <div class="col-span-full bg-white border border-dashed border-gray-200 rounded-2xl py-12 text-center text-gray-400 text-xs">
            Nenhum serviço cadastrado no momento. Comece criando um novo catálogo!
        </div>
    <?php else: foreach ($servicos as $s): 
        $sid   = (int)$s->getId();
        $nome  = $s->getNome();
        $desc  = $s->getDescricao();
        $dur   = (int)$s->getDuracaoMin();
        $prec  = (float)$s->getPreco();
        $ativo = $s->isAtivo();
        $foto  = $s->getFotoUrl();
        $pids  = $relMap[$sid] ?? [];
        
        // Texto descritivo legível de duração
        $durTexto = "{$dur} min";
        if ($dur === 30)  $durTexto = "30 Minutos";
        if ($dur === 60)  $durTexto = "1 Hora";
        if ($dur === 90)  $durTexto = "1h 30m";
        if ($dur === 120) $durTexto = "2 Horas";
    ?>
        <div class="sv-card bg-white border border-gray-100 rounded-2xl p-4 shadow-xs hover:shadow-md transition duration-200 flex flex-col justify-between gap-4"
             data-nome="<?=mb_strtolower($nome)?>" 
             data-ativo="<?=$ativo ? '1' : '0'?>">
            
            <div>
                <div class="flex items-start gap-3">
                    <?php if (!empty($foto)): ?>
                        <img src="<?=htmlspecialchars($foto)?>" alt="Serviço" class="w-12 h-12 rounded-xl object-cover shrink-0 border border-gray-100 shadow-inner">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-xl bg-pink-50 flex items-center justify-center text-pink-500 font-bold shrink-0 shadow-xs">✂️</div>
                    <?php endif; ?>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <h4 class="font-bold text-gray-800 text-xs truncate" title="<?=htmlspecialchars($nome)?>"><?=htmlspecialchars($nome)?></h4>
                            <button onclick="toggleStatusServico(<?=$sid?>, this)" class="shrink-0 text-[10px] px-2 py-0.5 rounded-full font-bold transition duration-150 <?=$ativo ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-rose-50 text-rose-500 hover:bg-rose-100'?>">
                                <?=$ativo ? 'Ativo' : 'Inativo'?>
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-400 line-clamp-2 leading-relaxed" title="<?=htmlspecialchars($desc)?>">
                            <?=htmlspecialchars($desc ?: 'Sem descrição informada.')?>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-gray-50 text-center">
                    <div class="bg-slate-50/60 rounded-xl p-1.5">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Duração</p>
                        <p class="text-xs font-bold text-gray-700 mt-0.5"><?=$durTexto?></p>
                    </div>
                    <div class="bg-slate-50/60 rounded-xl p-1.5">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Preço</p>
                        <p class="text-xs font-bold text-pink-600 mt-0.5">R$ <?=number_format($prec, 2, ',', '.')?></p>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-50 flex items-center justify-between gap-2">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Profissionais:</span>
                    <div class="flex items-center -space-x-1.5 overflow-hidden py-0.5">
                        <?php 
                        $count = 0;
                        if(empty($pids)): 
                            echo '<span class="text-[10px] text-gray-300 italic">Nenhum</span>';
                        else:
                            foreach ($pids as $pid) {
                                if (!isset($profMap[$pid])) continue;
                                $count++;
                                if ($count <= 3) {
                                    $pData = $profMap[$pid];
                                    if (!empty($pData['foto'])) {
                                        echo '<img src="'.htmlspecialchars($pData['foto']).'" class="w-5 h-5 rounded-full ring-2 ring-white object-cover" title="'.htmlspecialchars($pData['apelido']).'">';
                                    } else {
                                        $inicial = mb_substr($pData['apelido'], 0, 1);
                                        echo '<div class="w-5 h-5 rounded-full ring-2 ring-white text-white font-bold text-[8px] flex items-center justify-center shadow-xs" style="background:'.$pData['cor'].'" title="'.htmlspecialchars($pData['apelido']).'">'.$inicial.'</div>';
                                    }
                                }
                            }
                            if (count($pids) > 3) {
                                echo '<div class="w-5 h-5 rounded-full ring-2 ring-white bg-slate-800 text-white font-extrabold text-[8px] flex items-center justify-center shadow-xs">+'.(count($pids) - 3).'</div>';
                            }
                        endif;
                        ?>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <button onclick="editarServicoCarregar(<?=$sid?>)" class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-gray-600 text-[11px] font-bold rounded-xl transition border border-gray-100 flex items-center gap-1">
                        ✏️ Editar
                    </button>
                    <button onclick="deletarServico(<?=$sid?>)" class="p-1.5 bg-rose-50 text-rose-400 hover:bg-rose-100 hover:text-rose-600 transition rounded-xl border border-rose-100" title="Excluir Serviço">
                        🗑️
                    </button>
                </div>
            </div>

        </div>
    <?php endforeach; endif; ?>
</div>

<div id="modalServico" class="fixed inset-0 z-[999] flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs" onclick="fecharModalServico()"></div>
    
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform scale-95 transition-all duration-300 z-10 max-h-[85vh] flex flex-col">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
            <h3 id="modalServicoTitulo" class="font-bold text-gray-800 text-sm font-serif" style="font-family:'Playfair Display', serif">Configurar Serviço</h3>
            <button onclick="fecharModalServico()" class="w-6 h-6 rounded-lg bg-white shadow-xs border border-gray-100 flex items-center justify-center text-xs font-bold text-gray-400 hover:text-gray-600 transition">✕</button>
        </div>
        
        <form id="formServicoAdmin" onsubmit="salvarServicoSPA(event)" class="overflow-y-auto p-5 space-y-4 flex-1">
            <input type="hidden" id="sv_id" name="id" value="">

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nome do Serviço <span class="text-rose-500">*</span></label>
                <input type="text" id="sv_nome" name="nome" required placeholder="Ex: Atendimento Especializado" class="input-admin w-full text-xs py-2 bg-slate-50 border-gray-200 rounded-xl">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Preço (R$)</label>
                    <input type="number" step="0.01" id="sv_preco" name="preco" required placeholder="0,00" class="input-admin w-full text-xs py-2 bg-slate-50 border-gray-200 rounded-xl">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status Inicial</label>
                    <select id="sv_ativo" name="ativo" class="input-admin w-full text-xs py-2 bg-slate-50 border-gray-200 rounded-xl">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Duração Estimada</label>
                <select id="sv_duracao_select" onchange="manipularSelecaoDuracao(this.value)" class="input-admin w-full text-xs py-2 bg-slate-50 border-gray-200 rounded-xl">
                    <option value="30">30 Minutos</option>
                    <option value="60" selected>1 Hora</option>
                    <option value="90">1 Hora e 30 Minutos</option>
                    <option value="120">2 Horas</option>
                    <option value="custom">⏱️ Tempo Customizado...</option>
                </select>
                
                <div id="svDuracaoCustomBox" class="hidden mt-2">
                    <input type="number" id="sv_duracao_min" name="duracao_min" placeholder="Insira o tempo em minutos (ex: 150)" class="input-admin w-full text-xs py-2 bg-white border-pink-300 rounded-xl focus:ring-pink-100">
                </div>
            </div>

            <div class="bg-slate-50 border border-gray-200/60 rounded-xl p-3 text-[10px] text-gray-500 leading-relaxed shadow-inner">
                ⚠️ A duração do serviço deve respeitar o seu horário de funcionamento. Caso ultrapasse esse horário, o serviço não estará disponível para reserva. Para durações personalizadas acima de 2 horas, insira o tempo em minutos (ex.: 120 minutos).
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Descrição do Serviço</label>
                <textarea id="sv_descricao" name="descricao" rows="2" placeholder="Descreva os detalhes integrados..." class="input-admin w-full text-xs py-2 bg-slate-50 border-gray-200 rounded-xl resize-none"></textarea>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">URL da Imagem Ilustrativa</label>
                <div class="flex items-center gap-2">
                    <input type="url" id="sv_foto_url" name="foto_url" oninput="document.getElementById('svLivePreview').src=this.value || ''" placeholder="https://..." class="input-admin flex-1 text-xs py-2 bg-slate-50 border-gray-200 rounded-xl">
                    <img id="svLivePreview" src="" onerror="this.src=''" class="w-8 h-8 rounded-lg object-cover bg-slate-100 shrink-0 border border-gray-200">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-3">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Quem pode Atender?</label>
                    <button type="button" onclick="marcarTodosProfissionais()" class="text-[10px] text-pink-600 font-bold hover:underline">Selecionar Todos</button>
                </div>
                
                <div id="svProfsLista" class="grid grid-cols-2 gap-2 max-h-[120px] overflow-y-auto pr-1">
                    <?php foreach ($profissionais as $p): ?>
                        <label id="lbl_prof_<?=$p->getId()?>" class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-gray-100 cursor-pointer select-none transition hover:bg-slate-100 text-xs text-gray-700">
                            <input type="checkbox" name="profissionais[]" value="<?=$p->getId()?>" onchange="atualizarCheckItem(this)" class="rounded border-gray-300 text-pink-600 focus:ring-pink-50 w-3.5 h-3.5">
                            <span class="truncate"><?=htmlspecialchars($p->getApelido())?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" id="btnSalvarServico" class="btn-rosa-sm w-full py-2.5 font-bold text-xs rounded-xl shadow-md shadow-pink-100">
                    Salvar Serviço
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Filtro e Busca Local Instantâneo
function filtrarServicosLocal() {
    const busca = document.getElementById('svBusca').value.toLowerCase().trim();
    const status = document.getElementById('svFiltroStatus').value;
    const cards = document.querySelectorAll('#svGridCards .sv-card');

    cards.forEach(card => {
        const nome = card.getAttribute('data-nome') || '';
        const ativo = card.getAttribute('data-ativo') || '1';
        
        const bateBusca = busca === '' || nome.includes(busca);
        const bateStatus = status === 'todos' || 
                           (status === 'ativos' && ativo === '1') || 
                           (status === 'inativos' && ativo === '0');

        card.style.display = (bateBusca && bateStatus) ? 'flex' : 'none';
    });
}

// Abrir Modal Corrigido (Ativa classes de visibilidade e posição)
function abrirModalServico(titulo = 'Configurar Serviço') {
    document.getElementById('modalServicoTitulo').textContent = titulo;
    const modal = document.getElementById('modalServico');
    
    modal.classList.remove('pointer-events-none', 'opacity-0');
    const box = modal.querySelector('.transform');
    box.classList.remove('scale-95');
    box.classList.add('scale-100');
}

// Fechar Modal
function fecharModalServico() {
    const modal = document.getElementById('modalServico');
    modal.classList.add('pointer-events-none', 'opacity-0');
    
    const box = modal.querySelector('.transform');
    box.classList.remove('scale-100');
    box.classList.add('scale-95');
    
    document.getElementById('formServicoAdmin').reset();
    document.getElementById('svDuracaoCustomBox').classList.add('hidden');
    document.getElementById('svLivePreview').src = '';
    
    document.querySelectorAll('#svProfsLista input[type=checkbox]').forEach(cb => atualizarCheckItem(cb));
}

function manipularSelecaoDuracao(val) {
    const box = document.getElementById('svDuracaoCustomBox');
    const inp = document.getElementById('sv_duracao_min');
    if (val === 'custom') {
        box.classList.remove('hidden');
        inp.required = true;
    } else {
        box.classList.add('hidden');
        inp.value = val;
        inp.required = false;
    }
}

function atualizarCheckItem(cb) {
    const label = document.getElementById('lbl_prof_' + cb.value);
    if (!label) return;
    if (cb.checked) {
        label.classList.remove('bg-slate-50', 'border-gray-100');
        label.classList.add('bg-pink-50/40', 'border-pink-200');
    } else {
        label.classList.remove('bg-pink-50/40', 'border-pink-200');
        label.classList.add('bg-slate-50', 'border-gray-100');
    }
}

function marcarTodosProfissionais() {
    document.querySelectorAll('#svProfsLista input[type="checkbox"]').forEach(cb => {
        cb.checked = true;
        atualizarCheckItem(cb);
    });
}

function prepararNovoServico() {
    document.getElementById('sv_id').value = '';
    document.getElementById('formServicoAdmin').reset();
    document.getElementById('sv_duracao_select').value = "60";
    manipularSelecaoDuracao("60");
    abrirModalServico('✨ Adicionar Novo Serviço');
}

// Carrega os dados assincronamente via Fetch (Ação Editar)
function editarServicoCarregar(id) {
    document.getElementById('loadingBar').classList.add('visible');
    
    fetch(`${BASE}/api/servicos.php?acao=obter&id=${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loadingBar').classList.remove('visible');
        if (data.erro) {
            mostrarToast(data.erro, 'error');
            return;
        }

        document.getElementById('sv_id').value = data.id;
        document.getElementById('sv_nome').value = data.nome;
        document.getElementById('sv_preco').value = data.preco;
        document.getElementById('sv_ativo').value = data.ativo ? '1' : '0';
        document.getElementById('sv_descricao').value = data.descricao || '';
        document.getElementById('sv_foto_url').value = data.foto_url || '';
        document.getElementById('svLivePreview').src = data.foto_url || '';

        const dur = parseInt(data.duracao_min);
        const selectDur = document.getElementById('sv_duracao_select');
        const inputDur = document.getElementById('sv_duracao_min');
        
        if ([30, 60, 90, 120].includes(dur)) {
            selectDur.value = dur;
            document.getElementById('svDuracaoCustomBox').classList.add('hidden');
            inputDur.value = dur;
        } else {
            selectDur.value = 'custom';
            document.getElementById('svDuracaoCustomBox').classList.remove('hidden');
            inputDur.value = dur;
        }

        document.querySelectorAll('#svProfsLista input[type="checkbox"]').forEach(cb => {
            cb.checked = data.profissionais.includes(parseInt(cb.value));
            atualizarCheckItem(cb);
        });

        abrirModalServico('✏️ Editar Serviço');
    })
    .catch(() => {
        document.getElementById('loadingBar').classList.remove('visible');
        mostrarToast('Erro ao obter dados do serviço.', 'error');
    });
}

// Salva via POST sem dar refresh na página inteira (SPA-friendly)
async function salvarServicoSPA(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSalvarServico');
    btn.disabled = true;
    btn.innerHTML = '🕒 Salvando...';

    const formData = new FormData(document.getElementById('formServicoAdmin'));

    try {
        const res = await fetch(`${BASE}/api/servicos.php`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.sucesso) {
            fecharModalServico();
            mostrarToast('Serviço guardado com sucesso!', 'success');
            setTimeout(() => {
                const painel = document.getElementById('conteudo-painel');
                if (painel) {
                    fetch(`${BASE}/views/admin/abas/servicos.php`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.text())
                    .then(html => {
                        painel.innerHTML = html;
                        painel.querySelectorAll('script').forEach(s => {
                            const n = document.createElement('script');
                            n.textContent = s.textContent;
                            document.body.appendChild(n); n.remove();
                        });
                    });
                }
            }, 400);
        } else {
            mostrarToast(data.erro || 'Erro ao salvar serviço.', 'error');
        }
    } catch {
        mostrarToast('Erro de conexão.', 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = 'Salvar Serviço';
    }
}

// Altera o Status (Ativo/Inativo) diretamente no Card
function toggleStatusServico(id, btn) {
    document.getElementById('loadingBar').classList.add('visible');
    fetch(`${BASE}/api/servicos.php?acao=toggle&id=${id}`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loadingBar').classList.remove('visible');
        if (data.sucesso) {
            mostrarToast('Status atualizado!', 'success');
            if (data.ativo) {
                btn.className = 'shrink-0 text-[10px] px-2 py-0.5 rounded-full font-bold transition duration-150 bg-emerald-50 text-emerald-600 hover:bg-emerald-100';
                btn.textContent = 'Ativo';
                btn.closest('.sv-card').setAttribute('data-ativo', '1');
            } else {
                btn.className = 'shrink-0 text-[10px] px-2 py-0.5 rounded-full font-bold transition duration-150 bg-rose-50 text-rose-500 hover:bg-rose-100';
                btn.textContent = 'Inativo';
                btn.closest('.sv-card').setAttribute('data-ativo', '0');
            }
            filtrarServicosLocal();
        } else {
            mostrarToast(data.erro, 'error');
        }
    })
    .catch(() => {
        document.getElementById('loadingBar').classList.remove('visible');
        mostrarToast('Erro de rede ao alterar status.', 'error');
    });
}

// Remove o Serviço de forma definitiva (Ação Excluir)
function deletarServico(id) {
    if (!confirm('Deseja realmente eliminar este serviço de forma definitiva?')) return;

    document.getElementById('loadingBar').classList.add('visible');
    fetch(`${BASE}/api/servicos.php?acao=deletar&id=${id}`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loadingBar').classList.remove('visible');
        if (data.sucesso) {
            mostrarToast('Serviço removido com sucesso!', 'success');
            // Recarrega a aba atual da SPA
            const painel = document.getElementById('conteudo-painel');
            fetch(`${BASE}/views/admin/abas/servicos.php`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.text())
            .then(html => {
                painel.innerHTML = html;
                painel.querySelectorAll('script').forEach(s => {
                    const n = document.createElement('script');
                    n.textContent = s.textContent;
                    document.body.appendChild(n); n.remove();
                });
            });
        } else {
            mostrarToast(data.erro || 'Não foi possível remover o serviço.', 'error');
        }
    })
    .catch(() => {
        document.getElementById('loadingBar').classList.remove('visible');
        mostrarToast('Erro na conexão ao excluir o serviço.', 'error');
    });
}

/* Inicializa os estilos dos checkboxes na carga inicial */
document.querySelectorAll('#svProfsLista input[type=checkbox]').forEach(cb => atualizarCheckItem(cb));
</script>