<!DOCTYPE html>
<html>

  <head>
    <style>
      body {
        font-family: Arial, sans-serif;
      }

      .header {
        background-color: #083C4C;
        padding: 20px;
        text-align: center;
        font-size: 24px;
        color: white;
      }

      .content {
        margin: 20px;
        font-size: 16px;
        color: #083C4C;
      }

      .content p {
        color: #083C4C;
      }

      .main-button {
        padding: 8px 16px;
        background: #083C4C;
        text-decoration: none;
        color: white !important;
        text-transform: uppercase;
      }

      .footer {
        background-color: #083C4C;
        padding: 20px;
        text-align: center;
        font-size: 14px;
        color: white;
      }
    </style>
  </head>

  <body>

    <div class="header">
      <h2>Rafin Developer</h2>
    </div>

    <div class="content">
      <p>Dzień dobry, </p>
      <p>Zgodnie z Twoją prośbą przesyłamy Ci porównanie wybranych mieszkań.</p>
      <p>Twoja notatka:</p>
      <p>{message}</p>
      <p style="text-align: center;"><a class="main-button" target="_self" href="{compare-url}">Zobacz porównanie</a></p>
    </div>

    <div class="footer">
      <p>© 2023 Rafin Developer. Wszystkie prawa zastrzeżone.</p>
    </div>

  </body>

</html>