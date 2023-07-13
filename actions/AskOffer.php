<?php

if (!function_exists('\Breakdance\Forms\Actions\registerAction') || !class_exists('\Breakdance\Forms\Actions\Action')) {
    die;
}

class AskOffer extends Breakdance\Forms\Actions\Action
{

    /**
     * @return string
     */
    public static function name()
    {
        return 'Zapytaj o ofertę';
    }

    /**
     * @return string
     */
    public static function slug()
    {
        return 'ask_offer';
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
            $templateFile = DEVELOPER_SYSTEM_PLUGIN_DIR . '/templates/template-mail.php';
            if (!file_exists($templateFile)) {
                throw new Exception("Szablon maila nie istnieje: $templateFile");
            }
            
            $template = file_get_contents($templateFile);
            
            // Przygotowanie szablonu maila dla klienta
            $clientMessage = "Dziękujemy za kontakt. Otrzymaliśmy Twoją wiadomość i wkrótce odpowiemy.";
            
            $replacementsClient = [
                '{content}' => $clientMessage         
            ];
            
            $templateClient = str_replace(array_keys($replacementsClient), $replacementsClient, $template);
            
            // Przygotowanie szablonu maila dla administratora
            $adminMessage = "Nowa prośba o ofertę od klienta: $name. Wiadomość od klienta: $note. Link do porównywarki: $compareUrl.";
            
            $replacementsAdmin = [
                '{content}' => $adminMessage
            ];
            
            $templateAdmin = str_replace(array_keys($replacementsAdmin), $replacementsAdmin, $template);
            
            // Wyślij maila do klienta
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            if (!wp_mail($email, 'Potwierdzenie otrzymania wiadomości', $templateClient, $headers)) {
                throw new Exception('Nie udało się wysłać maila do klienta');
            }
            
            // Wyślij maila do administratora
            if (!wp_mail('sarkowal2000@gmail.com', 'Nowa prośba o ofertę', $templateAdmin, $headers)) { // Podmień 'adres email administratora' na prawdziwy adres email administratora
                throw new Exception('Nie udało się wysłać maila do administratora');
            }
            

        } catch (Exception $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        return ['type' => 'success', 'message' => 'Submission logged to file'];
    }
}

?>