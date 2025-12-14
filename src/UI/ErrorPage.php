<?php

namespace JiJiHoHoCoCo\IchiORM\UI;

class ErrorPage
{
    private static $errorPage = null;

    public static function setErrorPage($errorPage)
    {
        self::$errorPage = $errorPage;
    }

    public static function show(string $message, int $code)
    {
        if (self::$errorPage == null) {
            return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Arial', sans-serif;
      margin: 0;
      padding: 0;
    }

    header {
      background-color: #333;
      color: #FFFFFF;
      text-align: center;
      padding: 1rem;
    }

    section {
      max-width: 800px;
      margin: 2rem auto;
      padding: 1rem;
      background-color: 0000;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    footer {
      background-color: #333;
      color: 0000;
      text-align: center;
      padding: 1rem;
      position: fixed;
      bottom: 0;
      width: 100%;
    }

    /* Media queries for responsive design */

    @media only screen and (max-width: 600px) {
      section {
        margin: 1rem;
      }
    }
  </style>
  <title> $code - Error</title>
</head>
<body>
  <header>
    <h1>$code - Error</h1>
  </header>

  <section>
    <p>$message</p>
  </section>
</body>
</html>
HTML;
        }
        $errorPage = self::$errorPage;
        return is_callable($errorPage) ? $errorPage($message, $code) : $errorPage;
    }
}
