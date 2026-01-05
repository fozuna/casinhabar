<?php
require __DIR__ . '/../bootstrap.php';
use App\Services\Reports;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\AccountType;
use App\Models\Account;
use App\Models\Installment;
use App\Core\Auth as AAuth;
use App\Models\User;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$page = $_GET['page'] ?? 'dashboard';
$isAuth = isset($_SESSION['user_id']);
if (!$isAuth) {
    header('Location: login.php');
    exit;
}
ob_start();
switch ($page) {
    case 'dashboard':
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $ccId = isset($_GET['cost_center_id']) && $_GET['cost_center_id'] !== '' ? (int)$_GET['cost_center_id'] : null;
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'dashboard_pay') {
            $instId = (int)($_POST['installment_id'] ?? 0);
            $payDate = $_POST['pay_date'] ?? date('Y-m-d');
            $isAdmin = AAuth::requireRole(['admin']);
            if ($instId && $isAdmin) Installment::markPaidWithDate($instId, $payDate);
            header('Location: index.php?page=dashboard&start=' . urlencode($start ?? '') . '&end=' . urlencode($end ?? '') . '&cost_center_id=' . urlencode((string)($ccId ?? '')));
            exit;
        }
        $sum = Reports::summary($start, $end, $ccId);
        $series = Reports::seriesDaily($start, $end, $ccId);
        $split = Reports::flowSplit($start, $end, $ccId);
        $items = Reports::flowItems($start, $end, $ccId);
        $centers = CostCenter::all();
        $typesAll = AccountType::all();
        $typesReceita = array_values(array_filter($typesAll, function($t) { return ($t['kind'] ?? '') === 'receita'; }));
        $typesDespesa = array_values(array_filter($typesAll, function($t) { return ($t['kind'] ?? '') === 'despesa'; }));
        $dir = 'receita';
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
        echo '<div class="bg-white shadow rounded p-4"><div class="text-carbon_black-600 text-sm">Saldo até o momento</div><div class="text-2xl font-semibold text-imperial_blue-600">R$ ' . number_format($sum['saldoAteMomento'], 2, ',', '.') . '</div></div>';
        echo '<div class="bg-white shadow rounded p-4"><div class="text-carbon_black-600 text-sm">Receitas do período</div><div class="text-2xl font-semibold text-blue_bell-600">R$ ' . number_format($sum['receitasPeriodo'], 2, ',', '.') . '</div></div>';
        echo '<div class="bg-white shadow rounded p-4"><div class="text-carbon_black-600 text-sm">Despesas do período</div><div class="text-2xl font-semibold text-magenta_bloom-600">R$ ' . number_format($sum['despesasPeriodo'], 2, ',', '.') . '</div></div>';
        echo '</div>';
        echo '<div class="mt-6 bg-white shadow rounded p-4">';
        echo '<form class="grid grid-cols-1 md:grid-cols-5 gap-4" method="get" action="index.php">';
        echo '<input type="hidden" name="page" value="dashboard" />';
        echo '<div><label class="block text-sm mb-1">Início</label><input name="start" value="' . htmlspecialchars($start ?? '') . '" type="date" class="w-full border rounded px-3 py-2"></div>';
        echo '<div><label class="block text-sm mb-1">Fim</label><input name="end" value="' . htmlspecialchars($end ?? '') . '" type="date" class="w-full border rounded px-3 py-2"></div>';
        echo '<div><label class="block text-sm mb-1">Centro de custos</label><select name="cost_center_id" class="w-full border rounded px-3 py-2"><option value="">Todos</option>';
        foreach ($centers as $c) {
            $sel = ($ccId === (int)$c['id']) ? ' selected' : '';
            echo '<option value="' . intval($c['id']) . '"' . $sel . '>' . htmlspecialchars($c['name']) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="flex items-end"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Filtrar</button></div>';
        echo '</form>';
        echo '</div>';
        echo '<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">';
        echo '<div class="bg-white shadow rounded p-4 overflow-hidden">';
        echo '<div class="text-carbon_black-600 text-sm mb-2">Receitas x Despesas</div>';
        echo '<div class="aspect-video"><canvas id="chartLine" class="w-full h-full"></canvas></div>';
        echo '</div>';
        echo '<div class="bg-white shadow rounded p-4 overflow-hidden">';
        echo '<div class="text-carbon_black-600 text-sm mb-2">Fluxo de Caixa</div>';
        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
        echo '<div><div class="text-sm mb-1">Pendente</div><div class="aspect-video"><canvas id="chartPending" class="w-full h-full"></canvas></div></div>';
        echo '<div><div class="text-sm mb-1">Baixado</div><div class="aspect-video"><canvas id="chartPaid" class="w-full h-full"></canvas></div></div>';
        echo '</div>';
        echo '</div>';
        echo '<script>(function(){var typesMap={receita:' . json_encode(array_map(function($t){return ['id'=>$t['id'],'label'=>$t['name'].' • '.$t['cost_center_name']];}, $typesReceita)) . ',despesa:' . json_encode(array_map(function($t){return ['id'=>$t['id'],'label'=>$t['name'].' • '.$t['cost_center_name']];}, $typesDespesa)) . '};function bind(dirId,typeId){var d=document.getElementById(dirId),s=document.getElementById(typeId);if(!d||!s)return;function refill(){var list=typesMap[d.value||"' . $dir . '"]||[];s.innerHTML="";list.forEach(function(it){var o=document.createElement("option");o.value=it.id;o.textContent=it.label;s.appendChild(o);});}d.addEventListener("change",refill);refill();}bind("csvDir","csvType");bind("xlsxDir","xlsxType");})();</script>';
        echo '<script>(function(){var typesMap={receita:' . json_encode(array_map(function($t){return ['id'=>$t['id'],'label'=>$t['name'].' • '.$t['cost_center_name']];}, $typesReceita)) . ',despesa:' . json_encode(array_map(function($t){return ['id'=>$t['id'],'label'=>$t['name'].' • '.$t['cost_center_name']];}, $typesDespesa)) . '};function bind(dirId,typeId){var d=document.getElementById(dirId),s=document.getElementById(typeId);if(!d||!s)return;function refill(){var list=typesMap[d.value||\"receita\"]||[];s.innerHTML=\"\";list.forEach(function(it){var o=document.createElement(\"option\");o.value=it.id;o.textContent=it.label;s.appendChild(o);});}d.addEventListener(\"change\",refill);refill();}bind(\"csvDir\",\"csvType\");bind(\"xlsxDir\",\"xlsxType\");})();</script>';
        echo '</div>';
        echo '<div class="mt-6 bg-white shadow rounded p-4">';
        echo '<div class="text-carbon_black-600 text-sm mb-3">Fluxo de Caixa detalhado</div>';
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left"><th>Data</th><th>Tipo</th><th class="hidden sm:table-cell">Centro</th><th>Conta</th><th class="hidden sm:table-cell">Descrição</th><th class="hidden sm:table-cell">Documento</th><th class="hidden sm:table-cell">Parte</th><th class="text-right">Valor</th><th>Status</th><th class="w-40">Ação</th></tr></thead><tbody>';
        foreach ($items as $it) {
            $kindLbl = $it['kind']==='receita' ? '<span class="text-blue_bell-600">Receita</span>' : '<span class="text-magenta_bloom-600">Despesa</span>';
            $statusLbl = $it['status']==='paid' ? '<span class="text-green-600">Baixado</span>' : '<span class="text-carbon_black-600">Pendente</span>';
            echo '<tr class="border-t">';
            echo '<td class="py-2">' . htmlspecialchars($it['due_date']) . '</td>';
            echo '<td>' . $kindLbl . '</td>';
            echo '<td class="hidden sm:table-cell">' . htmlspecialchars($it['cost_center_name']) . '</td>';
            echo '<td>' . htmlspecialchars($it['account_type_name']) . '</td>';
            echo '<td class="hidden sm:table-cell">' . htmlspecialchars($it['description'] ?? '') . '</td>';
            echo '<td class="hidden sm:table-cell">' . htmlspecialchars($it['document'] ?? '') . '</td>';
            echo '<td class="hidden sm:table-cell">' . htmlspecialchars($it['party_name'] ?? '') . '</td>';
            echo '<td class="text-right">R$ ' . number_format((float)$it['amount'], 2, ',', '.') . '</td>';
            echo '<td>' . $statusLbl . '</td>';
            echo '<td>';
            if ($it['status'] === 'pending') {
                $isAdminDash = AAuth::requireRole(['admin']);
                if ($isAdminDash) {
                    echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Confirmar baixa?\')">';
                    echo '<input type="hidden" name="form" value="dashboard_pay" />';
                    echo '<input type="hidden" name="installment_id" value="' . intval($it['id']) . '" />';
                    echo '<input type="date" name="pay_date" class="border rounded px-2 py-1" value="' . htmlspecialchars(date('Y-m-d')) . '" /> ';
                    echo '<button class="px-3 py-1 rounded bg-blue_bell-600 text-white">Baixar</button>';
                    echo '</form>';
                } else {
                    echo '<span class="text-carbon_black-600">Somente admin</span>';
                }
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        echo '<script src="js/dashboard.js"></script>';
        echo '<script>(function(){var labels=' . json_encode($series['labels']) . ';var rec=' . json_encode($series['receitas']) . ';var des=' . json_encode($series['despesas']) . ';drawLineChart("chartLine",labels,rec,des,"#3e92cc","#d8315b");var pR=' . json_encode($split['pendingReceitas']) . ';var pD=' . json_encode($split['pendingDespesas']) . ';var paidR=' . json_encode($split['paidReceitas']) . ';var paidD=' . json_encode($split['paidDespesas']) . ';drawDoughnut("chartPending",[pR,pD],["#3e92cc","#d8315b"],["Receitas","Despesas"]);drawDoughnut("chartPaid",[paidR,paidD],["#3e92cc","#d8315b"],["Receitas","Despesas"]);})();</script>';
        break;
    case 'customers':
        if ($method === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $doc = trim($_POST['cpf_cnpj'] ?? '');
            $email = trim($_POST['email'] ?? '') ?: null;
            $phone = trim($_POST['phone'] ?? '') ?: null;
            $created = Customer::create($name, $doc, $email, $phone);
            if ($created) {
                header('Location: index.php?page=customers&ok=1');
                exit;
            } else {
                $error = 'Documento inválido ou já cadastrado';
            }
        }
        $list = Customer::all();
        echo '<div class="bg-white shadow rounded p-4">';
        echo '<div class="text-lg font-semibold mb-4">Clientes</div>';
        if (!empty($error)) echo '<div class="text-magenta_bloom-600 mb-3">' . htmlspecialchars($error) . '</div>';
        echo '<form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-6">';
        echo '<input name="name" placeholder="Nome" class="border rounded px-3 py-2" required />';
        echo '<input name="cpf_cnpj" placeholder="CPF ou CNPJ" class="border rounded px-3 py-2" required />';
        echo '<input name="email" placeholder="E-mail" class="border rounded px-3 py-2" />';
        echo '<input name="phone" placeholder="Telefone" class="border rounded px-3 py-2" />';
        echo '<div class="md:col-span-4"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Adicionar</button></div>';
        echo '</form>';
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left"><th class="py-2">Nome</th><th>Documento</th><th>E-mail</th><th>Telefone</th></tr></thead><tbody>';
        foreach ($list as $row) {
            echo '<tr class="border-t"><td class="py-2">' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['cpf_cnpj']) . '</td><td>' . htmlspecialchars($row['email'] ?? '') . '</td><td>' . htmlspecialchars($row['phone'] ?? '') . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        break;
    case 'suppliers':
        if ($method === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $doc = trim($_POST['cpf_cnpj'] ?? '');
            $email = trim($_POST['email'] ?? '') ?: null;
            $phone = trim($_POST['phone'] ?? '') ?: null;
            $created = Supplier::create($name, $doc, $email, $phone);
            if ($created) {
                header('Location: index.php?page=suppliers&ok=1');
                exit;
            } else {
                $error = 'Documento inválido ou já cadastrado';
            }
        }
        $list = Supplier::all();
        echo '<div class="bg-white shadow rounded p-4">';
        echo '<div class="text-lg font-semibold mb-4">Fornecedores</div>';
        if (!empty($error)) echo '<div class="text-magenta_bloom-600 mb-3">' . htmlspecialchars($error) . '</div>';
        echo '<form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-6">';
        echo '<input name="name" placeholder="Nome" class="border rounded px-3 py-2" required />';
        echo '<input name="cpf_cnpj" placeholder="CPF ou CNPJ" class="border rounded px-3 py-2" required />';
        echo '<input name="email" placeholder="E-mail" class="border rounded px-3 py-2" />';
        echo '<input name="phone" placeholder="Telefone" class="border rounded px-3 py-2" />';
        echo '<div class="md:col-span-4"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Adicionar</button></div>';
        echo '</form>';
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left"><th class="py-2">Nome</th><th>Documento</th><th>E-mail</th><th>Telefone</th></tr></thead><tbody>';
        foreach ($list as $row) {
            echo '<tr class="border-t"><td class="py-2">' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['cpf_cnpj']) . '</td><td>' . htmlspecialchars($row['email'] ?? '') . '</td><td>' . htmlspecialchars($row['phone'] ?? '') . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        break;
    case 'entries':
        $dir = $_GET['dir'] === 'despesa' ? 'despesa' : 'receita';
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'import') {
            $typeId = (int)($_POST['account_type_id'] ?? 0);
            $file = $_FILES['csv'] ?? null;
            $imported = 0; $failed = 0;
            if ($typeId && $file && ($file['error'] ?? 1) === 0) {
                $path = $file['tmp_name'];
                $fh = fopen($path, 'r');
                if ($fh) {
                    $header = fgets($fh);
                    $delim = (substr_count($header, ';') > substr_count($header, ',')) ? ';' : ',';
                    $cols = array_map('trim', explode($delim, $header));
                    $map = [];
                    foreach ($cols as $i=>$c) { $k = strtolower($c); $map[$k] = $i; }
                    while (($line = fgets($fh)) !== false) {
                        $parts = array_map('trim', explode($delim, $line));
                        $get = function($keys) use($map,$parts){ foreach ($keys as $k){ if(isset($map[$k])) return $parts[$map[$k]] ?? ''; } return ''; };
                        $dateStr = $get(['data','date','vencimento']);
                        $valStr = $get(['valor','amount']);
                        $partyName = preg_replace('/\s+/', ' ', trim($get(['parte','payee','fornecedor','cliente','conta'])));
                        $desc = $get(['descricao','description']);
                        $doc = $get(['documento','document']);
                        $status = strtolower($get(['status','pagamento']));
                        $monthMap = ['janeiro'=>1,'fevereiro'=>2,'março'=>3,'marco'=>3,'abril'=>4,'maio'=>5,'junho'=>6,'julho'=>7,'agosto'=>8,'setembro'=>9,'outubro'=>10,'novembro'=>11,'dezembro'=>12];
                        $cleanDate = strtolower(trim($dateStr));
                        $cleanDate = preg_replace('/^(segunda-feira|terça-feira|terca-feira|quarta-feira|quinta-feira|sexta-feira|sábado|sabado|domingo),?\s*/','',$cleanDate);
                        $date = null;
                        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $cleanDate)) { $date = $cleanDate; }
                        elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $cleanDate, $m)) { $date = $m[3].'-'.$m[2].'-'.$m[1]; }
                        elseif (preg_match('/^(\d{1,2})\s+de\s+([a-zçãé]+)\s+de\s+(\d{4})$/', $cleanDate, $m)) { $mm = $monthMap[$m[2]] ?? null; if ($mm) { $date = sprintf('%04d-%02d-%02d',(int)$m[3],$mm,(int)$m[1]); } }
                        if (!$date) { $date = date('Y-m-d'); }
                        $valStr = str_replace(['R$',' '],'',$valStr); $valStr = str_replace(['.'], '', $valStr); $valStr = str_replace([','], '.', $valStr);
                        $amount = (float)$valStr;
                        if ($amount <= 0) { $failed++; continue; }
                        $partyId = 0; $partyType = ($dir==='despesa') ? 'supplier' : 'customer';
                        if ($partyName !== '') {
                            if ($dir==='despesa') { $ex = App\Models\Supplier::findByName($partyName); $partyId = $ex ? (int)$ex['id'] : App\Models\Supplier::createPlaceholder($partyName); }
                            else { $ex = App\Models\Customer::findByName($partyName); $partyId = $ex ? (int)$ex['id'] : App\Models\Customer::createPlaceholder($partyName); }
                        }
                        try {
                            $accId = Account::create($typeId, $partyType, $partyId, $desc, $amount, 1, $date, $doc ?: null);
                            if (in_array($status, ['paid','baixado','pago'], true)) {
                                $inst = Installment::byAccount($accId);
                                if (!empty($inst)) Installment::markPaidWithDate((int)$inst[0]['id'], $date);
                            }
                            $imported++;
                        } catch (\Throwable $e) { $failed++; }
                    }
                    fclose($fh);
                }
            }
            header('Location: index.php?page=entries&dir=' . $dir . '&ok=' . $imported . '&fail=' . $failed);
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'import_json') {
            $typeId = (int)($_POST['account_type_id'] ?? 0);
            $payload = $_POST['json'] ?? '';
            $imported = 0; $failed = 0;
            if ($typeId && $payload) {
                $rows = json_decode($payload, true);
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $dateStr = trim((string)($r['date'] ?? ''));
                        $valStr = trim((string)($r['amount'] ?? ''));
                        $partyName = preg_replace('/\s+/', ' ', trim((string)($r['party'] ?? '')));
                        $desc = (string)($r['description'] ?? '');
                        $doc = (string)($r['document'] ?? '');
                        $status = strtolower(trim((string)($r['status'] ?? '')));
                        $monthMap = ['janeiro'=>1,'fevereiro'=>2,'março'=>3,'marco'=>3,'abril'=>4,'maio'=>5,'junho'=>6,'julho'=>7,'agosto'=>8,'setembro'=>9,'outubro'=>10,'novembro'=>11,'dezembro'=>12];
                        $cleanDate = strtolower($dateStr);
                        $cleanDate = preg_replace('/^(segunda-feira|terça-feira|terca-feira|quarta-feira|quinta-feira|sexta-feira|sábado|sabado|domingo),?\s*/','',$cleanDate);
                        $date = null;
                        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $cleanDate)) { $date = $cleanDate; }
                        elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $cleanDate, $m)) { $date = $m[3].'-'.$m[2].'-'.$m[1]; }
                        elseif (preg_match('/^(\d{1,2})\s+de\s+([a-zçãé]+)\s+de\s+(\d{4})$/', $cleanDate, $m)) { $mm = $monthMap[$m[2]] ?? null; if ($mm) { $date = sprintf('%04d-%02d-%02d',(int)$m[3],$mm,(int)$m[1]); } }
                        if (!$date) { $date = date('Y-m-d'); }
                        $valStr = str_replace(['R$',' '],'',$valStr); $valStr = str_replace(['.'], '', $valStr); $valStr = str_replace([','], '.', $valStr);
                        $amount = (float)$valStr;
                        if ($amount <= 0) { $failed++; continue; }
                        $partyId = 0; $partyType = ($dir==='despesa') ? 'supplier' : 'customer';
                        if ($partyName !== '') {
                            if ($dir==='despesa') { $ex = App\Models\Supplier::findByName($partyName); $partyId = $ex ? (int)$ex['id'] : App\Models\Supplier::createPlaceholder($partyName); }
                            else { $ex = App\Models\Customer::findByName($partyName); $partyId = $ex ? (int)$ex['id'] : App\Models\Customer::createPlaceholder($partyName); }
                        }
                        try {
                            $accId = Account::create($typeId, $partyType, $partyId, $desc, $amount, 1, $date, $doc ?: null);
                            if (in_array($status, ['paid','baixado','pago'], true)) {
                                $inst = Installment::byAccount($accId);
                                if (!empty($inst)) Installment::markPaidWithDate((int)$inst[0]['id'], $date);
                            }
                            $imported++;
                        } catch (\Throwable $e) { $failed++; }
                    }
                }
            }
            header('Location: index.php?page=entries&dir=' . $dir . '&ok=' . $imported . '&fail=' . $failed);
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'account') {
            $typeId = (int)($_POST['account_type_id'] ?? 0);
            $selectedParty = $_POST['party_type'] ?? 'customer';
            $partyRaw = $_POST['party_id'] ?? '';
            $partyType = $selectedParty; // para armazenar depois
            $partyId = 0;
            if (is_string($partyRaw)) {
                if (strpos($partyRaw, 'c_') === 0) { $partyType = 'customer'; $partyId = (int)substr($partyRaw, 2); }
                elseif (strpos($partyRaw, 's_') === 0) { $partyType = 'supplier'; $partyId = (int)substr($partyRaw, 2); }
                else { $partyId = (int)$partyRaw; }
            }
            if ($selectedParty === 'none') { $partyId = 0; $partyType = 'customer'; }
            $desc = trim($_POST['description'] ?? '');
            $total = (float)($_POST['total_amount'] ?? 0);
            $n = (int)($_POST['installments'] ?? 1);
            $firstDue = $_POST['first_due_date'] ?? date('Y-m-d');
            $document = $dir === 'despesa' ? trim($_POST['document'] ?? '') : null;
            $errors = [];
            if (!$typeId) $errors['account_type_id'] = 'Selecione o tipo';
            if ($selectedParty !== 'none' && $partyId <= 0) $errors['party_id'] = 'Selecione o vínculo';
            if ($total <= 0) $errors['total_amount'] = 'Informe o valor total';
            if ($n <= 0) $errors['installments'] = 'Informe as parcelas';
            if (!$firstDue) $errors['first_due_date'] = 'Informe o vencimento';
            if (empty($errors)) {
                Account::create($typeId, $partyType, $partyId, $desc, $total, $n, $firstDue, $document ?: null);
                header('Location: index.php?page=entries&dir=' . $dir);
                exit;
            } else {
                $error = 'Preencha: ' . implode(', ', array_values($errors));
            }
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'pay') {
            $instId = (int)($_POST['installment_id'] ?? 0);
            $payDate = $_POST['pay_date'] ?? date('Y-m-d');
            $isAdmin = AAuth::requireRole(['admin']);
            if ($instId && $isAdmin) Installment::markPaidWithDate($instId, $payDate);
            header('Location: index.php?page=entries&dir=' . $dir);
            exit;
        }
        $typesAll = AccountType::all();
        $types = array_values(array_filter($typesAll, function($t) use ($dir) { return ($t['kind'] ?? '') === $dir; }));
        $typesReceita = array_values(array_filter($typesAll, function($t) { return ($t['kind'] ?? '') === 'receita'; }));
        $typesDespesa = array_values(array_filter($typesAll, function($t) { return ($t['kind'] ?? '') === 'despesa'; }));
        $customers = Customer::all();
        $suppliers = Supplier::all();
        $accountsAll = Account::all();
        $accounts = array_values(array_filter($accountsAll, function($a) use ($dir) { return ($a['kind'] ?? '') === $dir; }));
        echo '<div class="bg-white shadow rounded p-4">';
        echo '<div class="flex items-center justify-between mb-3">';
        echo '<div class="text-lg font-semibold">Lançamentos • ' . ($dir === 'receita' ? 'Receitas' : 'Despesas') . '</div>';
        echo '<div class="text-sm">';
        echo '<a class="px-3 py-1 rounded ' . ($dir==='receita'?'bg-blue_bell-600 text-white':'bg-white border') . '" href="index.php?page=entries&dir=receita">Receitas</a> ';
        echo '<a class="px-3 py-1 rounded ' . ($dir==='despesa'?'bg-magenta_bloom-600 text-white':'bg-white border') . '" href="index.php?page=entries&dir=despesa">Despesas</a>';
        echo '</div>';
        echo '</div>';
        echo '<script>(function(){var map={receita:' . json_encode(array_map(function($t){return ['id'=>$t['id'],'label'=>$t['name'].' • '.$t['cost_center_name']];}, $typesReceita)) . ',despesa:' . json_encode(array_map(function($t){return ['id'=>$t['id'],'label'=>$t['name'].' • '.$t['cost_center_name']];}, $typesDespesa)) . '};document.querySelectorAll(\"form\").forEach(function(f){var d=f.querySelector(\"select[name=dir]\");var t=f.querySelector(\"select[name=account_type_id]\");if(!d||!t){return;}function refill(){var list=map[d.value]||[];t.innerHTML=\"\";list.forEach(function(it){var o=document.createElement(\"option\");o.value=it.id;o.textContent=it.label;t.appendChild(o);});}d.addEventListener(\"change\",refill);});})();</script>';
        if (!empty($error)) echo '<div class="text-magenta_bloom-600 mb-3">' . htmlspecialchars($error) . '</div>';
        echo '<form method="post" class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-6">';
        echo '<input type="hidden" name="form" value="account" />';
        $clsType = !empty($errors['account_type_id']) ? 'border-magenta_bloom-500 ring-1 ring-magenta_bloom-500' : 'border';
        $typeMap = [];
        foreach ($types as $t) { $typeMap[] = ['id'=>$t['id'], 'label'=>$t['name'].' • '.$t['kind'].' • '.$t['cost_center_name']]; }
        echo '<input list="type_list" name="type_label" placeholder="Tipo de conta" class="' . $clsType . ' rounded px-3 py-2 md:col-span-2" />';
        echo '<datalist id="type_list">';
        foreach ($typeMap as $tm) { echo '<option value="' . htmlspecialchars($tm['label']) . '"></option>'; }
        echo '</datalist>';
        echo '<input type="hidden" name="account_type_id" />';
        $clsPartyType = !empty($errors['party_id']) ? 'border-magenta_bloom-500 ring-1 ring-magenta_bloom-500' : 'border';
        echo '<select name="party_type" class="' . $clsPartyType . ' rounded px-3 py-2">';
        echo '<option value="customer">Cliente</option><option value="supplier">Fornecedor</option><option value="none">Sem vínculo</option>';
        echo '</select>';
        $partyMap = [];
        foreach ($customers as $c) { $partyMap[] = ['id'=>'c_'.intval($c['id']), 'label'=>'Cliente • '.$c['name']]; }
        foreach ($suppliers as $s) { $partyMap[] = ['id'=>'s_'.intval($s['id']), 'label'=>'Fornecedor • '.$s['name']]; }
        echo '<input list="party_list" name="party_label" placeholder="Cliente ou Fornecedor" class="' . $clsPartyType . ' rounded px-3 py-2" />';
        echo '<datalist id="party_list">';
        foreach ($partyMap as $pm) { echo '<option value="' . htmlspecialchars($pm['label']) . '"></option>'; }
        echo '</datalist>';
        echo '<input type="hidden" name="party_id" />';
        echo '<script>(function(){var types=' . json_encode($typeMap) . ';var parties=' . json_encode($partyMap) . ';function mapInputToHidden(inpName,list,hiddenName){var inp=document.querySelector("[name="+inpName+"]");var hid=document.querySelector("[name="+hiddenName+"]");if(!inp||!hid)return;inp.addEventListener("change",function(){var v=inp.value;var f=list.find(function(x){return x.label===v});hid.value=f?f.id:"";});} mapInputToHidden("type_label",types,"account_type_id");mapInputToHidden("party_label",parties,"party_id");})();</script>';
        echo '<input name="description" placeholder="Descrição" class="border rounded px-3 py-2 md:col-span-2" />';
        $clsDoc = 'border';
        if ($dir === 'despesa') { echo '<input name="document" placeholder="Documento (NF, recibo, etc.)" class="' . $clsDoc . ' rounded px-3 py-2 md:col-span-2" />'; }
        $clsTotal = !empty($errors['total_amount']) ? 'border-magenta_bloom-500 ring-1 ring-magenta_bloom-500' : 'border';
        $clsInst = !empty($errors['installments']) ? 'border-magenta_bloom-500 ring-1 ring-magenta_bloom-500' : 'border';
        $clsDate = !empty($errors['first_due_date']) ? 'border-magenta_bloom-500 ring-1 ring-magenta_bloom-500' : 'border';
        echo '<input name="total_amount" type="number" step="0.01" placeholder="Valor total" class="' . $clsTotal . ' rounded px-3 py-2" required />';
        echo '<input name="installments" type="number" min="1" placeholder="Parcelas" class="' . $clsInst . ' rounded px-3 py-2" required />';
        echo '<input name="first_due_date" type="date" class="' . $clsDate . ' rounded px-3 py-2" required />';
        echo '<div class="md:col-span-6"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Cadastrar</button></div>';
        echo '</form>';
        echo '<div class="mt-6 bg-white shadow rounded p-5">';
        echo '<div class="text-lg font-semibold mb-4">Importar Lançamentos • ' . ($dir==='receita'?'Receitas':'Despesas') . '</div>';
        echo '<div class="space-y-6">';
        echo '<div class="border rounded-xl p-4">';
        echo '<div class="text-sm font-medium mb-3">CSV</div>';
        echo '<form method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">';
        echo '<input type="hidden" name="form" value="import" />';
        echo '<input type="hidden" name="dir" value="' . htmlspecialchars($dir) . '" />';
        echo '<div><label class="block text-xs mb-1">Tipo de conta</label><select name="account_type_id" class="form-select border rounded px-3 py-2" required>';
        foreach ($types as $t) { echo '<option value="' . intval($t['id']) . '">' . htmlspecialchars($t['name']) . ' • ' . htmlspecialchars($t['cost_center_name']) . '</option>'; }
        echo '</select></div>';
        echo '<div><label class="block text-xs mb-1">Arquivo</label><input type="file" name="csv" accept=".csv,text/csv" class="form-input border rounded px-3 py-2" required /></div>';
        echo '<div><button class="bg-imperial_blue-600 hover:bg-imperial_blue-700 transition text-white px-4 py-2 rounded">Importar CSV</button></div>';
        echo '</form>';
        echo '<div class="mt-2 text-xs text-carbon_black-600">Cabecalhos aceitos: data, valor, parte, descricao, documento, status • Delimitadores , ou ;</div>';
        echo '</div>';
        echo '<div class="border rounded-xl p-4">';
        echo '<div class="text-sm font-medium mb-3">Excel (.xlsx)</div>';
        echo '<form method="post" id="excelForm" class="flex flex-wrap items-end gap-3">';
        echo '<input type="hidden" name="form" value="import_json" />';
        echo '<input type="hidden" name="dir" value="' . htmlspecialchars($dir) . '" />';
        echo '<div><label class="block text-xs mb-1">Tipo de conta</label><select name="account_type_id" class="form-select border rounded px-3 py-2" required>';
        foreach ($types as $t) { echo '<option value="' . intval($t['id']) . '">' . htmlspecialchars($t['name']) . ' • ' . htmlspecialchars($t['cost_center_name']) . '</option>'; }
        echo '</select></div>';
        echo '<div><label class="block text-xs mb-1">Arquivo</label><input id="xlsxFile" type="file" accept=".xlsx,.xls" class="form-input border rounded px-3 py-2" /></div>';
        echo '<div><button type="button" id="btnParse" class="bg-imperial_blue-600 hover:bg-imperial_blue-700 transition text-white px-4 py-2 rounded">Pré-visualizar</button></div>';
        echo '</form>';
        echo '<div id="mapping" class="mt-4 hidden">';
        echo '<div class="flex flex-wrap gap-3">';
        echo '<select id="map_date" class="form-select border rounded px-3 py-2"></select>';
        echo '<select id="map_amount" class="form-select border rounded px-3 py-2"></select>';
        echo '<select id="map_party" class="form-select border rounded px-3 py-2"></select>';
        echo '<select id="map_desc" class="form-select border rounded px-3 py-2"></select>';
        echo '<select id="map_doc" class="form-select border rounded px-3 py-2"></select>';
        echo '<select id="map_status" class="form-select border rounded px-3 py-2"></select>';
        echo '</div>';
        echo '<div class="mt-3"><table class="w-full text-xs" id="preview"><thead></thead><tbody></tbody></table></div>';
        echo '<input type="hidden" name="json" id="jsonPayload" />';
        echo '<div class="mt-3"><button id="btnImportExcel" class="bg-imperial_blue-600 hover:bg-imperial_blue-700 transition text-white px-4 py-2 rounded">Importar Excel</button></div>';
        echo '</div>';
        echo '</div>';
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.19.3/xlsx.full.min.js"></script>';
        echo '<script src="js/import.js"></script>';
        echo '</div>';
        foreach ($accounts as $a) {
            echo '<div class="border rounded p-3 mb-4">';
            $isAdmin = AAuth::requireRole(['admin']);
            echo '<div class="flex items-center justify-between">';
            echo '<div class="font-medium">' . htmlspecialchars($a['account_type_name']) . ' • ' . htmlspecialchars($a['kind']) . ' • ' . htmlspecialchars($a['cost_center_name']) . '</div>';
            echo '<div class="flex items-center gap-2">';
            echo '<a title="Editar" href="index.php?page=entries&dir=' . $dir . '&edit_account=' . intval($a['id']) . '" class="inline-flex items-center justify-center w-8 h-8 rounded border"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/><path d="M20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0L15.13 5.12l3.75 3.75 1.83-1.83z"/></svg></a>';
            if ($isAdmin) {
                echo '<form method="post" style="display:inline"><input type="hidden" name="form" value="account_delete" /><input type="hidden" name="account_id" value="' . intval($a['id']) . '" /><button title="Excluir" class="inline-flex items-center justify-center w-8 h-8 rounded border"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button></form>';
            }
            echo '</div>';
            echo '</div>';
            echo '<div class="text-sm text-carbon_black-600">' . htmlspecialchars($a['description'] ?? '') . '</div>';
            echo '<div class="text-sm">' . htmlspecialchars($a['party_name'] ?? '') . '</div>';
            $inst = Installment::byAccount((int)$a['id']);
            echo '<table class="w-full text-sm mt-2">';
            echo '<thead><tr class="text-left">' . ($dir==='despesa' ? '<th>Doc</th>' : '') . '<th>Nº</th><th>Vencimento</th><th>Valor</th><th>Status</th><th>Ação</th></tr></thead><tbody>';
            foreach ($inst as $i) {
                $status = $i['status'] === 'paid' ? '<span class="text-green-600">Pago</span>' : '<span class="text-magenta_bloom-600">Pendente</span>';
                echo '<tr class="border-t">';
                if ($dir==='despesa') { echo '<td class="py-2">' . htmlspecialchars($a['document'] ?? '') . '</td>'; }
                echo '<td class="py-2">' . intval($i['number']) . '</td><td>' . htmlspecialchars($i['due_date']) . '</td><td>R$ ' . number_format((float)$i['amount'], 2, ',', '.') . '</td><td>' . $status . '</td><td>';
                if ($i['status'] === 'pending') {
                    if ($isAdmin) {
                        echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Confirmar baixa?\')"><input type="hidden" name="form" value="pay" /><input type="hidden" name="installment_id" value="' . intval($i['id']) . '" />';
                        echo '<input type="date" name="pay_date" class="border rounded px-2 py-1" value="' . htmlspecialchars(date('Y-m-d')) . '" /> ';
                        echo '<button class="px-3 py-1 rounded bg-blue_bell-600 text-white" title="Dar baixa">Baixar</button></form>';
                    } else {
                        echo '<span class="text-carbon_black-600">Somente admin</span>';
                    }
                }
                echo '</td></tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        }
        echo '</div>';
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'account_delete') {
            $accId = (int)($_POST['account_id'] ?? 0);
            $isAdmin = AAuth::requireRole(['admin']);
            if ($accId && $isAdmin) Account::delete($accId);
            header('Location: index.php?page=entries&dir=' . $dir);
            exit;
        }
        if (isset($_GET['edit_account'])) {
            $editId = (int)$_GET['edit_account'];
            foreach ($accounts as $a) { if ((int)$a['id'] === $editId) { $ea = $a; break; } }
            if (!empty($ea)) {
                echo '<div class="border rounded p-3 mb-4">';
                echo '<div class="text-lg font-semibold mb-3">Editar Lançamento</div>';
                echo '<form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3">';
                echo '<input type="hidden" name="form" value="account_update" /><input type="hidden" name="account_id" value="' . intval($ea['id']) . '" />';
                echo '<input name="description" value="' . htmlspecialchars($ea['description'] ?? '') . '" class="border rounded px-3 py-2 md:col-span-2" />';
                if ($dir==='despesa') { echo '<input name="document" value="' . htmlspecialchars($ea['document'] ?? '') . '" class="border rounded px-3 py-2" />'; }
                echo '<div class="md:col-span-3"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Salvar</button></div>';
                echo '</form>';
                echo '</div>';
            }
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'account_update') {
            $accId = (int)($_POST['account_id'] ?? 0);
            $descU = trim($_POST['description'] ?? '');
            $docU = isset($_POST['document']) ? trim($_POST['document']) : null;
            if ($accId) Account::update($accId, $descU, $docU);
            header('Location: index.php?page=entries&dir=' . $dir);
            exit;
        }
        break;
    case 'cost-centers':
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'cc') {
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '') ?: null;
            if ($name !== '') CostCenter::create($name, $desc);
            header('Location: index.php?page=cost-centers');
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'type') {
            $name = trim($_POST['name'] ?? '');
            $kind = $_POST['kind'] ?? 'receita';
            $cc = (int)($_POST['cost_center_id'] ?? 0);
            if ($name !== '' && $cc > 0) AccountType::create($name, $kind, $cc);
            header('Location: index.php?page=cost-centers');
            exit;
        }
        $centers = CostCenter::listAll();
        $types = AccountType::all();
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'cc_update') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '') ?: null;
            if ($id && $name) CostCenter::update($id, $name, $desc);
            header('Location: index.php?page=cost-centers');
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'cc_toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $active = (int)($_POST['active'] ?? 1) === 1;
            if ($id) CostCenter::toggle($id, $active);
            header('Location: index.php?page=cost-centers');
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'cc_delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id && !CostCenter::delete($id)) { $error = 'Centro possui tipos vinculados'; }
            header('Location: index.php?page=cost-centers');
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'type_update') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $kind = $_POST['kind'] ?? 'receita';
            $cc = (int)($_POST['cost_center_id'] ?? 0);
            if ($id && $name && $cc) AccountType::update($id, $name, $kind, $cc);
            header('Location: index.php?page=cost-centers');
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'type_toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $active = (int)($_POST['active'] ?? 1) === 1;
            if ($id) AccountType::toggle($id, $active);
            header('Location: index.php?page=cost-centers');
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'type_delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id && !AccountType::delete($id)) { $error = 'Tipo possui lançamentos vinculados'; }
            header('Location: index.php?page=cost-centers');
            exit;
        }
        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        echo '<div class="bg-white shadow rounded p-4">';
        echo '<div class="text-lg font-semibold mb-3">Centros de Custos</div>';
        echo '<form method="post" class="grid grid-cols-1 gap-3 mb-4">';
        echo '<input type="hidden" name="form" value="cc" />';
        echo '<input name="name" placeholder="Nome" class="border rounded px-3 py-2" required />';
        echo '<input name="description" placeholder="Descrição" class="border rounded px-3 py-2" />';
        echo '<button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Adicionar</button>';
        echo '</form>';
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left"><th class="py-2">Nome</th><th>Status</th><th class="w-40">Ações</th></tr></thead><tbody>';
        foreach ($centers as $c) {
            $badge = ((int)$c['active']===1) ? '<span class="text-green-600">Ativo</span>' : '<span class="text-carbon_black-600">Inativo</span>';
            echo '<tr class="border-t"><td class="py-2">' . htmlspecialchars($c['name']) . '</td><td>' . $badge . '</td><td class="flex items-center gap-2 py-2">';
            echo '<form method="post" style="display:inline"><input type="hidden" name="form" value="cc_toggle" /><input type="hidden" name="id" value="' . intval($c['id']) . '" /><input type="hidden" name="active" value="' . (((int)$c['active']===1)?0:1) . '" /><button title="' . (((int)$c['active']===1)?'Inativar':'Ativar') . '" aria-label="' . (((int)$c['active']===1)?'Inativar':'Ativar') . '" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue_bell-600 text-white">' . (((int)$c['active']===1)?'<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5z"/><circle cx="12" cy="12" r="3"/></svg>':'<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M13 3h-2v10h2V3zm-1 19C7.48 22 3.5 18.52 3.5 14c0-3.38 2.19-6.24 5.22-7.3l.66 1.88A7.01 7.01 0 005 14c0 3.86 3.14 7 7 7s7-3.14 7-7a7.01 7.01 0 00-4.38-6.42l.66-1.88A9 9 0 0120.5 14c0 4.52-3.98 8-8.5 8z"/></svg>') . '</button></form>';
            echo '<a title="Editar" aria-label="Editar" href="index.php?page=cost-centers&edit_cc=' . intval($c['id']) . '" class="inline-flex items-center justify-center w-8 h-8 rounded border"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/><path d="M20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0L15.13 5.12l3.75 3.75 1.83-1.83z"/></svg></a>';
            echo '<form method="post" style="display:inline"><input type="hidden" name="form" value="cc_delete" /><input type="hidden" name="id" value="' . intval($c['id']) . '" /><button title="Excluir" aria-label="Excluir" class="inline-flex items-center justify-center w-8 h-8 rounded border"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button></form>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        echo '<div class="bg-white shadow rounded p-4">';
        echo '<div class="text-lg font-semibold mb-3">Tipos de Contas</div>';
        echo '<form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">';
        echo '<input type="hidden" name="form" value="type" />';
        echo '<input name="name" placeholder="Nome" class="border rounded px-3 py-2" required />';
        echo '<select name="kind" class="border rounded px-3 py-2"><option value="receita">Receita</option><option value="despesa">Despesa</option></select>';
        echo '<select name="cost_center_id" class="border rounded px-3 py-2">';
        foreach ($centers as $c) {
            echo '<option value="' . intval($c['id']) . '">' . htmlspecialchars($c['name']) . '</option>';
        }
        echo '</select>';
        echo '<div class="md:col-span-4"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Adicionar</button></div>';
        echo '</form>';
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left"><th class="py-2">Nome</th><th>Tipo</th><th>Centro de Custos</th><th>Status</th><th class="w-40">Ações</th></tr></thead><tbody>';
        foreach ($types as $t) {
            $badge = ((int)($t['active'] ?? 1)===1) ? '<span class="text-green-600">Ativo</span>' : '<span class="text-carbon_black-600">Inativo</span>';
            echo '<tr class="border-t"><td class="py-2">' . htmlspecialchars($t['name']) . '</td><td>' . htmlspecialchars($t['kind']) . '</td><td>' . htmlspecialchars($t['cost_center_name']) . '</td><td>' . $badge . '</td><td class="flex items-center gap-2 py-2">';
            echo '<form method="post" style="display:inline"><input type="hidden" name="form" value="type_toggle" /><input type="hidden" name="id" value="' . intval($t['id']) . '" /><input type="hidden" name="active" value="' . (((int)($t['active'] ?? 1)===1)?0:1) . '" /><button title="' . (((int)($t['active'] ?? 1)===1)?'Inativar':'Ativar') . '" aria-label="' . (((int)($t['active'] ?? 1)===1)?'Inativar':'Ativar') . '" class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue_bell-600 text-white">' . (((int)($t['active'] ?? 1)===1)?'<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5z"/><circle cx="12" cy="12" r="3"/></svg>':'<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M13 3h-2v10h2V3zm-1 19C7.48 22 3.5 18.52 3.5 14c0-3.38 2.19-6.24 5.22-7.3l.66 1.88A7.01 7.01 0 005 14c0 3.86 3.14 7 7 7s7-3.14 7-7a7.01 7.01 0 00-4.38-6.42l.66-1.88A9 9 0 0120.5 14c0 4.52-3.98 8-8.5 8z"/></svg>') . '</button></form>';
            echo '<a title="Editar" aria-label="Editar" href="index.php?page=cost-centers&edit_type=' . intval($t['id']) . '" class="inline-flex items-center justify-center w-8 h-8 rounded border"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/><path d="M20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0L15.13 5.12l3.75 3.75 1.83-1.83z"/></svg></a>';
            echo '<form method="post" style="display:inline"><input type="hidden" name="form" value="type_delete" /><input type="hidden" name="id" value="' . intval($t['id']) . '" /><button title="Excluir" aria-label="Excluir" class="inline-flex items-center justify-center w-8 h-8 rounded border"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button></form>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        echo '</div>';
        if (isset($_GET['edit_cc'])) {
            $editId = (int)$_GET['edit_cc'];
            foreach ($centers as $c) { if ((int)$c['id'] === $editId) { $ec = $c; break; } }
            if (!empty($ec)) {
                echo '<div class="mt-4 bg-white shadow rounded p-4">';
                echo '<div class="text-lg font-semibold mb-3">Editar Centro de Custos</div>';
                echo '<form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3">';
                echo '<input type="hidden" name="form" value="cc_update" /><input type="hidden" name="id" value="' . intval($ec['id']) . '" />';
                echo '<input name="name" value="' . htmlspecialchars($ec['name']) . '" class="border rounded px-3 py-2" required />';
                echo '<input name="description" value="' . htmlspecialchars($ec['description'] ?? '') . '" class="border rounded px-3 py-2" />';
                echo '<div class="md:col-span-3"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Salvar</button></div>';
                echo '</form>';
                echo '</div>';
            }
        }
        if (isset($_GET['edit_type'])) {
            $editId = (int)$_GET['edit_type'];
            foreach ($types as $t) { if ((int)$t['id'] === $editId) { $et = $t; break; } }
            if (!empty($et)) {
                echo '<div class="mt-4 bg-white shadow rounded p-4">';
                echo '<div class="text-lg font-semibold mb-3">Editar Tipo de Conta</div>';
                echo '<form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-3">';
                echo '<input type="hidden" name="form" value="type_update" /><input type="hidden" name="id" value="' . intval($et['id']) . '" />';
                echo '<input name="name" value="' . htmlspecialchars($et['name']) . '" class="border rounded px-3 py-2" required />';
                echo '<select name="kind" class="border rounded px-3 py-2"><option value="receita"' . ($et['kind']==='receita'?' selected':'') . '>Receita</option><option value="despesa"' . ($et['kind']==='despesa'?' selected':'') . '>Despesa</option></select>';
                echo '<select name="cost_center_id" class="border rounded px-3 py-2">';
                foreach ($centers as $c) { $sel = ((int)$c['id'] === (int)$et['cost_center_id']) ? ' selected' : ''; echo '<option value="' . intval($c['id']) . '"' . $sel . '>' . htmlspecialchars($c['name']) . '</option>'; }
                echo '</select>';
                echo '<div class="md:col-span-4"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Salvar</button></div>';
                echo '</form>';
                echo '</div>';
            }
        }
        break;
    case 'users':
        $canManage = AAuth::requireRole(['admin']);
        if ($method === 'POST' && $canManage) {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'viewer';
            if ($name && $email && $password) {
                User::create($name, $email, $password, $role);
                header('Location: index.php?page=users');
                exit;
            }
        }
        $pdo = App\Core\Database::getConnection();
        $users = $pdo->query('SELECT id, name, email, role FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        echo '<div class="bg-white shadow rounded p-4">';
        echo '<div class="text-lg font-semibold mb-3">Usuários</div>';
        if ($canManage) {
            echo '<form method="post" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-6">';
            echo '<input name="name" placeholder="Nome" class="border rounded px-3 py-2" required />';
            echo '<input name="email" type="email" placeholder="E-mail" class="border rounded px-3 py-2" required />';
            echo '<input name="password" type="password" placeholder="Senha" class="border rounded px-3 py-2" required />';
            echo '<select name="role" class="border rounded px-3 py-2"><option value="viewer">Leitor</option><option value="manager">Gestor</option><option value="admin">Admin</option></select>';
            echo '<div class="md:col-span-5"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Adicionar</button></div>';
            echo '</form>';
        } else {
            echo '<div class="text-sm text-carbon_black-600 mb-3">Apenas administradores podem cadastrar usuários.</div>';
        }
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left"><th class="py-2">Nome</th><th>E-mail</th><th>Papel</th></tr></thead><tbody>';
        foreach ($users as $u) {
            echo '<tr class="border-t"><td class="py-2">' . htmlspecialchars($u['name']) . '</td><td>' . htmlspecialchars($u['email']) . '</td><td>' . htmlspecialchars($u['role']) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        break;
    case 'import':
        $dir = ($_POST['dir'] ?? $_GET['dir'] ?? 'receita') === 'despesa' ? 'despesa' : 'receita';
        $typesAll = AccountType::all();
        $types = array_values(array_filter($typesAll, function($t) use ($dir) { return ($t['kind'] ?? '') === $dir; }));
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'import_json') {
            $typeId = (int)($_POST['account_type_id'] ?? 0);
            $payload = $_POST['json'] ?? '';
            $imported = 0; $failed = 0;
            if ($typeId && $payload) {
                $rows = json_decode($payload, true);
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $dateStr = trim((string)($r['date'] ?? ''));
                        $valStr = trim((string)($r['amount'] ?? ''));
                        $partyName = trim((string)($r['party'] ?? ''));
                        $desc = (string)($r['description'] ?? '');
                        $doc = (string)($r['document'] ?? '');
                        $status = strtolower(trim((string)($r['status'] ?? '')));
                        $monthMap = ['janeiro'=>1,'fevereiro'=>2,'março'=>3,'marco'=>3,'abril'=>4,'maio'=>5,'junho'=>6,'julho'=>7,'agosto'=>8,'setembro'=>9,'outubro'=>10,'novembro'=>11,'dezembro'=>12];
                        $cleanDate = strtolower($dateStr);
                        $cleanDate = preg_replace('/^(segunda-feira|terça-feira|terca-feira|quarta-feira|quinta-feira|sexta-feira|sábado|sabado|domingo),?\s*/','',$cleanDate);
                        $date = null;
                        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $cleanDate, $m)) { $date = $cleanDate; }
                        elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $cleanDate, $m)) { $date = $m[3].'-'.$m[2].'-'.$m[1]; }
                        elseif (preg_match('/^(\d{1,2})\s+de\s+([a-zçãé]+)\s+de\s+(\d{4})$/', $cleanDate, $m)) { $mm = $monthMap[$m[2]] ?? null; if ($mm) { $date = sprintf('%04d-%02d-%02d',(int)$m[3],$mm,(int)$m[1]); } }
                        if (!$date) { $date = date('Y-m-d'); }
                        $valStr = str_replace(['R$',' '],'',$valStr); $valStr = str_replace(['.'], '', $valStr); $valStr = str_replace([','], '.', $valStr);
                        $amount = (float)$valStr;
                        if ($amount <= 0) { $failed++; continue; }
                        $partyId = 0; $partyType = ($dir==='despesa') ? 'supplier' : 'customer';
                        if ($partyName !== '') {
                            $partyName = preg_replace('/\s+/',' ', $partyName);
                            if ($dir==='despesa') { $ex = App\Models\Supplier::findByName($partyName); $partyId = $ex ? (int)$ex['id'] : App\Models\Supplier::createPlaceholder($partyName); }
                            else { $ex = App\Models\Customer::findByName($partyName); $partyId = $ex ? (int)$ex['id'] : App\Models\Customer::createPlaceholder($partyName); }
                        }
                        try {
                            $accId = Account::create($typeId, $partyType, $partyId, $desc, $amount, 1, $date, $doc ?: null);
                            if (in_array($status, ['paid','baixado','pago'], true)) {
                                $inst = Installment::byAccount($accId);
                                if (!empty($inst)) Installment::markPaidWithDate((int)$inst[0]['id'], $date);
                            }
                            $imported++;
                        } catch (\Throwable $e) { $failed++; }
                    }
                }
            }
            header('Location: index.php?page=import&dir=' . $dir . '&ok=' . $imported . '&fail=' . $failed);
            exit;
        }
        if ($method === 'POST' && ($_POST['form'] ?? '') === 'import') {
            $typeId = (int)($_POST['account_type_id'] ?? 0);
            $file = $_FILES['csv'] ?? null;
            $imported = 0; $failed = 0;
            if ($typeId && $file && ($file['error'] ?? 1) === 0) {
                $path = $file['tmp_name'];
                $fh = fopen($path, 'r');
                if ($fh) {
                    $header = fgets($fh);
                    $delim = (substr_count($header, ';') > substr_count($header, ',')) ? ';' : ',';
                    $cols = array_map('trim', explode($delim, $header));
                    $map = [];
                    foreach ($cols as $i=>$c) { $k = strtolower($c); $map[$k] = $i; }
                    while (($line = fgets($fh)) !== false) {
                        $parts = array_map('trim', explode($delim, $line));
                        $get = function($keys) use($map,$parts){ foreach ($keys as $k){ if(isset($map[$k])) return $parts[$map[$k]] ?? ''; } return ''; };
                        $dateStr = $get(['data','date','vencimento']);
                        $valStr = $get(['valor','amount']);
                        $partyName = $get(['parte','payee','fornecedor','cliente','conta']);
                        $partyName = preg_replace('/\s+/',' ', trim($partyName));
                        $desc = $get(['descricao','description']);
                        $doc = $get(['documento','document']);
                        $status = strtolower($get(['status','pagamento']));
                        $monthMap = ['janeiro'=>1,'fevereiro'=>2,'março'=>3,'marco'=>3,'abril'=>4,'maio'=>5,'junho'=>6,'julho'=>7,'agosto'=>8,'setembro'=>9,'outubro'=>10,'novembro'=>11,'dezembro'=>12];
                        $cleanDate = strtolower(trim($dateStr));
                        $cleanDate = preg_replace('/^(segunda-feira|terça-feira|terca-feira|quarta-feira|quinta-feira|sexta-feira|sábado|sabado|domingo),?\s*/','',$cleanDate);
                        $date = null;
                        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $cleanDate, $m)) {
                            $date = $cleanDate;
                        } elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $cleanDate, $m)) {
                            $date = $m[3].'-'.$m[2].'-'.$m[1];
                        } elseif (preg_match('/^(\d{1,2})\s+de\s+([a-zçãé]+)\s+de\s+(\d{4})$/', $cleanDate, $m)) {
                            $mm = $monthMap[$m[2]] ?? null; $dd = (int)$m[1]; $yy = (int)$m[3];
                            if ($mm) { $date = sprintf('%04d-%02d-%02d',$yy,$mm,$dd); }
                        }
                        if (!$date) { $date = date('Y-m-d'); }
                        $valStr = str_replace(['R$',' '],'',$valStr); $valStr = str_replace(['.'], '', $valStr); $valStr = str_replace([','], '.', $valStr);
                        $amount = (float)$valStr;
                        if ($amount <= 0) { $failed++; continue; }
                        $partyId = 0; $partyType = ($dir==='despesa') ? 'supplier' : 'customer';
                        if ($partyName !== '') {
                            if ($dir==='despesa') { $ex = App\Models\Supplier::findByName($partyName); $partyId = $ex ? (int)$ex['id'] : App\Models\Supplier::createPlaceholder($partyName); }
                            else { $ex = App\Models\Customer::findByName($partyName); $partyId = $ex ? (int)$ex['id'] : App\Models\Customer::createPlaceholder($partyName); }
                        }
                        try {
                            $accId = Account::create($typeId, $partyType, $partyId, $desc, $amount, 1, $date, $doc ?: null);
                            if ($status === 'paid' || $status === 'baixado' || $status === 'pago') {
                                $inst = Installment::byAccount($accId);
                                if (!empty($inst)) Installment::markPaidWithDate((int)$inst[0]['id'], $date);
                            }
                            $imported++;
                        } catch (\Throwable $e) { $failed++; }
                    }
                    fclose($fh);
                }
            }
            header('Location: index.php?page=import&dir=' . $dir . '&ok=' . $imported . '&fail=' . $failed);
            exit;
        }
        echo '<div class="bg-white shadow rounded p-4">';
        echo '<div class="text-lg font-semibold mb-3">Importar Lançamentos</div>';
        if (isset($_GET['ok'])) {
            echo '<div class="mb-3 text-sm">Importados: ' . intval($_GET['ok']) . ' • Falhas: ' . intval($_GET['fail'] ?? 0) . '</div>';
        }
        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
        echo '<form id="csvForm" method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-3">';
        echo '<input type="hidden" name="form" value="import" />';
        echo '<select id="csvDir" name="dir" class="border rounded px-3 py-2"><option value="despesa"' . ($dir==='despesa'?' selected':'') . '>Despesas</option><option value="receita"' . ($dir==='receita'?' selected':'') . '>Receitas</option></select>';
        echo '<select id="csvType" name="account_type_id" class="border rounded px-3 py-2" required>'; 
        foreach ($types as $t) { echo '<option value="' . intval($t['id']) . '">' . htmlspecialchars($t['name']) . ' • ' . htmlspecialchars($t['cost_center_name']) . '</option>'; }
        echo '</select>';
        echo '<input type="file" name="csv" accept=".csv,text/csv" class="border rounded px-3 py-2" required />';
        echo '<div class="md:col-span-3"><button class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Importar</button></div>';
        echo '</form>';
        echo '<div class="border rounded p-4">';
        echo '<div class="text-sm font-medium mb-2">Importar via Excel (.xlsx)</div>';
        echo '<form method="post" id="excelForm" class="space-y-3">';
        echo '<input type="hidden" name="form" value="import_json" />';
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">';
        echo '<select id="xlsxDir" name="dir" class="border rounded px-3 py-2"><option value="despesa"' . ($dir==='despesa'?' selected':'') . '>Despesas</option><option value="receita"' . ($dir==='receita'?' selected':'') . '>Receitas</option></select>';
        echo '<select id="xlsxType" name="account_type_id" class="border rounded px-3 py-2" required>'; 
        foreach ($types as $t) { echo '<option value="' . intval($t['id']) . '">' . htmlspecialchars($t['name']) . ' • ' . htmlspecialchars($t['cost_center_name']) . '</option>'; }
        echo '</select>';
        echo '<input id="xlsxFile" type="file" accept=".xlsx,.xls" class="border rounded px-3 py-2" />';
        echo '<button type="button" id="btnParse" class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Pré-visualizar</button>';
        echo '</div>';
        echo '<div id="mapping" class="mt-3 hidden">';
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">';
        echo '<select id="map_date" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_amount" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_party" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_desc" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_doc" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_status" class="border rounded px-3 py-2"></select>';
        echo '</div>';
        echo '<div class="mt-3"><table class="w-full text-xs" id="preview"><thead></thead><tbody></tbody></table></div>';
        echo '<input type="hidden" name="json" id="jsonPayload" />';
        echo '<div class="mt-3"><button id="btnImportExcel" class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Importar Excel</button></div>';
        echo '</div>';
        echo '</form>';
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.19.3/xlsx.full.min.js"></script>';
        echo '<script src="js/import.js"></script>';
        echo '</div>';
        echo '<div class="border rounded p-4 mt-6">';
        echo '<div class="text-sm font-medium mb-2">Importar via Copiar/Colar</div>';
        echo '<form method="post" id="pasteForm" class="space-y-3">';
        echo '<input type="hidden" name="form" value="import_json" />';
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">';
        echo '<select id="xlsxDir" name="dir" class="border rounded px-3 py-2"><option value="despesa"' . ($dir==='despesa'?' selected':'') . '>Despesas</option><option value="receita"' . ($dir==='receita'?' selected':'') . '>Receitas</option></select>';
        echo '<select id="xlsxType" name="account_type_id" class="border rounded px-3 py-2" required>'; 
        foreach ($types as $t) { echo '<option value="' . intval($t['id']) . '">' . htmlspecialchars($t['name']) . ' • ' . htmlspecialchars($t['cost_center_name']) . '</option>'; }
        echo '</select>';
        echo '<label class="inline-flex items-center gap-2"><input id="pasteHasHeader" type="checkbox" class="form-checkbox" /> <span class="text-sm">Primeira linha é cabeçalho</span></label>';
        echo '<button type="button" id="btnParsePaste" class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Pré-visualizar</button>';
        echo '</div>';
        echo '<textarea id="pasteArea" class="w-full border rounded px-3 py-2 h-40" placeholder="Cole aqui dados separados por ; , ou Tab"></textarea>';
        echo '<div id="mappingPaste" class="mt-3 hidden">';
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">';
        echo '<select id="map_date2" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_amount2" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_party2" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_desc2" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_doc2" class="border rounded px-3 py-2"></select>';
        echo '<select id="map_status2" class="border rounded px-3 py-2"></select>';
        echo '</div>';
        echo '<div class="mt-3"><table class="w-full text-xs" id="previewPaste"><thead></thead><tbody></tbody></table></div>';
        echo '<input type="hidden" name="json" id="jsonPayloadPaste" />';
        echo '<div class="mt-3"><button id="btnImportPaste" class="bg-imperial_blue-600 text-white px-4 py-2 rounded">Importar Copiar/Colar</button></div>';
        echo '</div>';
        echo '<script src="js/import_paste.js"></script>';
        echo '<div class="mt-4 text-sm prose"><p>Formato esperado (CSV com cabeçalho):</p><pre>data,valor,parte,descricao,documento,status\n2025-12-05,680.00,Fábio,Serviço,2308,paid</pre><p>Delimitadores "," ou ";" são aceitos. Datas "dd/mm/aaaa" também.</p></div>';
        echo '</div>';
        break;
    default:
        http_response_code(404);
        echo '<div class="bg-white shadow rounded p-4">Página não encontrada</div>';
}
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';

