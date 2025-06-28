<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bienvenido a Cañalimeña Group</title>
  <style>
    body {
      margin: 0;
      padding: 20px;
      background-color: #f9f9f9;
      font-family: Arial, sans-serif;
      color: #333;
    }
    .container {
      background-color: #ffffff;
      max-width: 700px;
      margin: auto;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    .logo img {
      max-width: 200px;
      margin-bottom: 20px;
    }
    h1 {
      color: #597c4c;
      font-size: 26px;
      margin-bottom: 10px;
    }
    p {
      font-size: 16px;
      line-height: 1.5;
    }
    .highlight {
      background-color: #f2c029;
      color: #000;
      font-weight: bold;
      padding: 10px 20px;
      display: inline-block;
      margin: 20px 0;
      border-radius: 4px;
      font-size: 18px;
    }
    .brands-title {
      margin-top: 30px;
      font-size: 14px;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .brands {
      margin-top: 10px;
    }
    .brands img {
      height: 50px;
      margin: 10px;
      vertical-align: middle;
    }
    .promo-code {
      background-color: #dff0d8;
      color: #3c763d;
      display: inline-block;
      padding: 10px 20px;
      border-radius: 5px;
      font-size: 18px;
      font-weight: bold;
      margin-top: 20px;
    }
    .footer {
      margin-top: 40px;
      font-size: 12px;
      color: #999;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <img src="cid:logoGrupo" alt="Cañalimeña Group">
    </div>
    <h1>¡Gracias por confiar en Cañalimeña Group, {{NOMBRE}}!</h1>
    <p>¡Estamos encantados de que nos hayas compartido tu correo electrónico!</p>
    <p>Como muestra de nuestro agradecimiento, queremos invitarte a disfrutar de una deliciosa:</p>
    <div class="highlight">
      ¡Jamaica GRATIS!
    </div>
    <p>Podrás canjearla en tu <strong>próxima compra o servicio</strong> en cualquiera de nuestras marcas:</p>
    <ul style="list-style:none; padding:0; font-size:16px; line-height:1.5; text-align: left; display: inline-block; margin: 20px auto;">
      <li>✔ Consumo en <strong>BurguerBeer</strong></li>
      <li>✔ Alquiler de Jeep o cuatrón en <strong>Aninga Travel</strong></li>
      <li>✔ Estadía en <strong>Cañalimeña Hostal & Suites</strong></li>
      <li>✔ Desayuno en <strong>Calikaphe</strong></li>
    </ul>
    <p>Solo muestra este correo en caja o recepción al momento de tu compra y disfruta de tu bebida de cortesía. ¡Te esperamos pronto!</p>

    <p><strong>Código único para canjear tu promoción:</strong></p>
    <div class="promo-code">
      {{CODIGO}}
    </div>

    <div class="brands-title">
      Cañalimeña Group integra:
    </div>

    <div class="brands">
      <img src="cid:logoAninga" alt="Aninga Travel">
      <img src="cid:logoBurguer" alt="BurguerBeer">
      <img src="cid:logoCali" alt="Calikaphe">
      <img src="cid:logoHostal" alt="Hostal & Suites">
    </div>

    <div class="footer">
      Cañalimeña Group &copy; 2025. Todos los derechos reservados.
    </div>
  </div>
</body>
</html>
