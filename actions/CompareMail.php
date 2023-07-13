<?php

if (!function_exists('\Breakdance\Forms\Actions\registerAction') || !class_exists('\Breakdance\Forms\Actions\Action')) {
    die;
}

class CompareMail extends Breakdance\Forms\Actions\Action
{

    /**
     * @return string
     */
    public static function name()
    {
        return 'Mail z porównywarki ';
    }

    /**
     * @return string
     */
    public static function slug()
    {
        return 'compare_mail';
    }

    public function run($form, $settings, $extra)
    {
        try {
            // Upewniamy się, że wszystkie oczekiwane wartości są dostępne
            if (!isset($extra['fields']['name'], $extra['fields']['email'], $extra['fields']['message'], $extra['fields']['compare-url'])) {
                throw new Exception('Nie wszystkie oczekiwane wartości są dostępne w tablicy $extra.');
            }

            $name = $extra['fields']['name'];
            $email = $extra['fields']['email'];
            $note = $extra['fields']['message'];
            $compareUrl = $extra['fields']['compare-url'];

            // Sprawdzenie, czy plik szablonu istnieje
            $templateFile = DEVELOPER_SYSTEM_PLUGIN_DIR . '/templates/compare-mail.php';
            if (!file_exists($templateFile)) {
                throw new Exception("Szablon maila nie istnieje: $templateFile");
            }

            $template = file_get_contents($templateFile);

            // Przygotowanie szablonu maila
            $replacements = [
                '{name}' => $name,
                '{email}' => $email,
                '{message}' => $note,
                '{compare-url}' => $compareUrl,
            ];

            $template = str_replace(array_keys($replacements), $replacements, $template);

            // Wyślij maila
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            if (!wp_mail($email, 'Porównanie mieszkań', $template, $headers)) {
                throw new Exception('Nie udało się wysłać maila');
            }

        } catch (Exception $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        return ['type' => 'success', 'message' => 'Submission logged to file'];
    }
}

?>