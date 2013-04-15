<html>
	<head>
		<title>Images test</title>
		<style type="text/css">
			html,body{
				width: 100%;
				height: 100%;
			}
			div.products{
				width:100%;
				text-align: center;
			}
			div.products div.conf {
				width:90%;
				margin: 0 auto;
				text-align: left;
			}
			div.products div.conf div.left,
			div.products div.conf div.right{
				float: left;
				width: 49%;
				padding: 5px;
				border: 1px solid #ccc;
			}
			div.products div.conf h5{
				font-size: 13px;
			}
			div.products div.conf img.conf_image{
				float: left;
				margin: 5px;
			}
		</style>
	</head>
	<body>
		<?php

		require 'app/Mage.php';
		Mage::setIsDeveloperMode(true);
		ini_set('display_errors', 1);
		error_reporting(E_ALL | E_STRICT);

		Mage::app();

		$factory = Mage::getModel('tcimport/import');
		$config = $factory::getConnectionConfig();
		$config['dbname'] = 'otto';
		$connection = Mage::getModel('core/resource')->createConnection('import', $config['type'], $config);
		$query = "select i.id, i.url, i.name, i.init_image from art_init as i limit 40";
		$configurables = $connection->fetchAll($query);
		?>
		<div class="products">
			<?php foreach ($configurables as $conf):?>
				<?php $query = "
						SELECT
							 `params`.`dim1`, `params`.`dim2`,
							 `params`.`dim3`, `params`.`dim4`, `params`.`dim5`,
							 `params`.`image_main` AS `main_image`,
							 `params`.`image_all` AS `init_image`FROM `params`
						WHERE (id='{$conf['id']}') 
						GROUP BY CONCAT_WS('_', dim1,dim2,dim3,dim4,dim5,id,price)";

						$simples = $connection->fetchAll($query);
				?>
				<div class="conf">
					<div class="left">
						<h4>Configurable product original id: <?php echo $conf['id'];?></h4>
						Original link : <a href="<?php echo $conf['url'];?>" target="_blank" >go to otto.de</a><br/>
						Original name : <b><?php echo $conf['name'];?></b><br/>
						<h5 style="color:red;">Images:</h5>
						<?php 
							$images = explode('***foto***', $conf['init_image'] );
						?>
						<?php foreach ($images as $image) :?>
							<img class="conf_image" width="100" height="150" src="http://zgamz.net/extt/<?php echo $image;?>" alt="Configurable image" />
						<?php endforeach; ?>
						<div style="clear:both;"></div>
					</div>
					<div class="right">
						<h4>Simple products:</h4>
						<?php foreach ($simples as $simple) :?>
							Dim 1 : <b><?php echo $simple['dim1'];?></b><br />
							Dim 2 : <b><?php echo $simple['dim2'];?></b><br />
							Dim 3 : <b><?php echo $simple['dim3'];?></b><br />
							Dim 4 : <b><?php echo $simple['dim4'];?></b><br />
							Dim 5 : <b><?php echo $simple['dim5'];?></b><br />
							<h5 style="color:red;">Images:</h5>
							<?php 
								$images = explode('***foto***', $simple['init_image'] );
								$mainImages = explode('***foto***', $simple['main_image'] );
							?>
							<?php foreach ($images as $image) :?>
								<img class="conf_image" width="100" height="150" src="http://zgamz.net/extt/<?php echo $image;?>" alt="Simple image" />
							<?php endforeach; ?>
							<div style="clear:both;"></div>
							<h5 style="color:red;">Main images:</h5>
							<?php foreach ($mainImages as $image) :?>
								<img class="conf_image" width="100" height="150" src="http://zgamz.net/extt/<?php echo $image;?>" alt="Simple image" />
							<?php endforeach; ?>
							<div style="clear:both;"></div>
							<br />
							<hr />
						<?php endforeach; ?>
					</div>
					<div style="clear:both;"></div>
				</div>
			<?php endforeach; ?>
		</div>
	</body>
</html>