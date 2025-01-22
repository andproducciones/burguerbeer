<?php 

if(empty($_SESSION['active']))
{
	header('location: ../index2.php');
}
?>
<header>
		<div class="header">
			<div style="align-content:  left;">
			<h1 >Ala 23</h1>
			</div>
			
			
			<div class="optionsBar">
				<p class="fecha">Ecuador, <?php echo fechaC(); ?></p>
				<span class="fecha">|</span>
				<span class="user"><?php echo $_SESSION['nombre']." ".$_SESSION['p_apellido']; ?></span>
				<img class="photouser" src="img/user.png" alt="Usuario">
				<a href="salir.php"><img class="close" src="img/salir.png" alt="Salir del Sistema" title="Salir"></a>
			</div> 
		</div>
	</header>

	<div class="modal">
		<div class="bodyModal">
		</div>
	</div>