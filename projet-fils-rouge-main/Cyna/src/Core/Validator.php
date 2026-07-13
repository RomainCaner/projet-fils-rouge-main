<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Validation de formulaires côté serveur.
 *
 * Les règles sont déclarées sous forme de chaîne « rule|rule:param ». Cette
 * validation serveur complète (et ne remplace pas) la validation côté client :
 * elle constitue le rempart de sécurité contre les données malveillantes.
 */
final class Validator
{
    /** @var array<string,list<string>> */
    private array $errors = [];

    /**
     * @param array<string,mixed>  $data
     * @param array<string,string> $rules   ex: ['email' => 'required|email']
     * @param array<string,string> $labels  Libellés lisibles par champ
     */
    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $labels = [],
    ) {
    }

    /**
     * @param array<string,mixed>  $data
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     */
    public static function make(array $data, array $rules, array $labels = []): self
    {
        $validator = new self($data, $rules, $labels);
        $validator->validate();

        return $validator;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $ruleString);

            // Un champ non requis et vide n'est pas validé plus avant.
            if (!in_array('required', $rules, true) && ($value === null || $value === '')) {
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, (string) $name, $param, $value);
            }
        }
    }

    private function applyRule(string $field, string $rule, ?string $param, mixed $value): void
    {
        $label = $this->labels[$field] ?? $field;
        $string = is_scalar($value) ? (string) $value : '';

        switch ($rule) {
            case 'required':
                if ($value === null || $string === '') {
                    $this->addError($field, "Le champ {$label} est obligatoire.");
                }
                break;

            case 'email':
                if (!filter_var($string, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Le champ {$label} doit être une adresse e-mail valide.");
                }
                break;

            case 'min':
                if (mb_strlen($string) < (int) $param) {
                    $this->addError($field, "Le champ {$label} doit contenir au moins {$param} caractères.");
                }
                break;

            case 'max':
                if (mb_strlen($string) > (int) $param) {
                    $this->addError($field, "Le champ {$label} ne doit pas dépasser {$param} caractères.");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "Le champ {$label} doit être numérique.");
                }
                break;

            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    $this->addError($field, "La confirmation du champ {$label} ne correspond pas.");
                }
                break;

            case 'same':
                if (($this->data[$param] ?? null) !== $value) {
                    $this->addError($field, "Le champ {$label} ne correspond pas.");
                }
                break;

            case 'in':
                if (!in_array($string, explode(',', (string) $param), true)) {
                    $this->addError($field, "La valeur du champ {$label} est invalide.");
                }
                break;

            case 'password':
                // Au moins 8 caractères, majuscule, minuscule, chiffre et caractère spécial.
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $string)) {
                    $this->addError($field, "Le {$label} doit faire au moins 8 caractères avec majuscule, minuscule, chiffre et caractère spécial.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string,list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return list<string> */
    public function allMessages(): array
    {
        return array_merge(...array_values($this->errors)) ?: [];
    }
}
