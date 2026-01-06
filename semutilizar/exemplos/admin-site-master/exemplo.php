<?php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
		<!-- MAIN -->
		<main>
			<h1 class="title">Ano Letivo</h1>
			<ul class="breadcrumbs">
				<li><a href="#">Cadastros</a></li>
				<li class="divider">/</li>
				<li><a href="#" class="active">Ano Letivo</a></li>
			</ul>
			<div class="info-data">
				<div class="card">
					<div class="head">
						<div>
							<h2>1500</h2>
							<p>Traffic</p>
						</div>
						<i class='bx bx-trending-up icon'></i>
					</div>
					<span class="progress" data-value="40%"></span>
					<span class="label">40%</span>
				</div>
				<div class="card">
					<div class="head">
						<div>
							<h2>234</h2>
							<p>Sales</p>
						</div>
						<i class='bx bx-trending-down icon down'></i>
					</div>
					<span class="progress" data-value="60%"></span>
					<span class="label">60%</span>
				</div>
				<div class="card">
					<div class="head">
						<div>
							<h2>465</h2>
							<p>Pageviews</p>
						</div>
						<i class='bx bx-trending-up icon'></i>
					</div>
					<span class="progress" data-value="30%"></span>
					<span class="label">30%</span>
				</div>
				<div class="card">
					<div class="head">
						<div>
							<h2>235</h2>
							<p>Visitors</p>
						</div>
						<i class='bx bx-trending-up icon'></i>
					</div>
					<span class="progress" data-value="80%"></span>
					<span class="label">80%</span>
				</div>
			</div>
			<div class="data">
				<div class="content-data">
					<div class="head">
						<h3>Contatos</h3>
					</div>
					<div class="table-data">
						<table>
							<thead>
								<tr>
									<th>Nome</th>
									<th>Endereço</th>
									<th>E-mail</th>
									<th>Ações</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>João Silva</td>
									<td>Rua A, 123</td>
									<td>joao@email.com</td>
									<td>
										<button class="btn-edit">Editar</button>
										<button class="btn-delete">Deletar</button>
										<button class="btn-print">Imprimir</button>
									</td>
								</tr>
								<tr>
									<td>Maria Souza</td>
									<td>Avenida B, 456</td>
									<td>maria@email.com</td>
									<td>
										<button class="btn-edit">Editar</button>
										<button class="btn-delete">Deletar</button>
										<button class="btn-print">Imprimir</button>
									</td>
								</tr>
								<!-- Adicione mais linhas conforme necessário -->
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</main>
		<!-- MAIN -->
	</section>
<!-- CONTENT -->
<script src="<?php echo JS_PATH; ?>/script.js"></script>
</body>
</html>