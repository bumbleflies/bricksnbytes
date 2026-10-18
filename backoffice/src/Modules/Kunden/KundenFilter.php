<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

// What the customer list is filtered by: search term (top search bar) AND the dropdowns Typ,
// Newsletter, Ort and Status. Empty / "Alle" = no filter. Pure (no database), so the SQL it builds
// can be unit-tested; list, mail addresses, export and bulk actions all use the same filter.
final class KundenFilter
{
    // Newsletter values the data model knows (active consent or not)
    public const NEWSLETTER = ['ja' => 'Ja', 'nein' => 'Nein'];
    public const STATUS_NONE = 'ohne';

    // A newsletter consent counts as active when it was given and not withdrawn
    private const NEWSLETTER_ACTIVE = "SELECT 1 FROM einwilligungen e WHERE e.kunde_id = k.id AND e.kind_id IS NULL
          AND e.art = 'newsletter' AND e.erteilt_am IS NOT NULL AND e.widerrufen_am IS NULL";

    public function __construct(
        public readonly string $q = '',
        public readonly ?string $typ = null,
        public readonly ?string $newsletter = null,
        public readonly ?string $ort = null,
        public readonly ?string $status = null,
    ) {
    }

    /** From URL or form parameters; unknown or empty values ("Alle") mean "no filter" */
    public static function fromQuery(array $input): self
    {
        $str = static fn (string $key): string => is_string($input[$key] ?? null) ? trim((string) preg_replace('/\s+/u', ' ', $input[$key])) : '';
        $typ = $str('typ');
        $newsletter = $str('newsletter');
        $ort = $str('ort');
        $status = $str('status');
        return new self(
            mb_substr($str('q'), 0, 200),
            Kundentypen::valid($typ) ? $typ : null,
            isset(self::NEWSLETTER[$newsletter]) ? $newsletter : null,
            $ort !== '' ? mb_substr($ort, 0, 100) : null,
            $status === self::STATUS_NONE || Kundentypen::validStatus($status) ? $status : null,
        );
    }

    /** Only the search term, e.g. for the global search page */
    public static function search(string $q): self
    {
        return new self(trim($q));
    }

    public static function newsletterOnly(): self
    {
        return new self('', null, 'ja');
    }

    public function isEmpty(): bool
    {
        return $this->q === '' && $this->typ === null && $this->newsletter === null && $this->ort === null && $this->status === null;
    }

    /** Set parameters for URLs and hidden fields (same names as fromQuery reads) */
    public function params(): array
    {
        return array_filter(
            ['q' => $this->q, 'typ' => $this->typ, 'newsletter' => $this->newsletter, 'ort' => $this->ort, 'status' => $this->status],
            static fn ($v) => $v !== null && $v !== ''
        );
    }

    /**
     * WHERE clause (all conditions AND-combined) and its placeholder values, for queries on `kunden k`.
     *
     * @return array{0: string, 1: list<mixed>}
     */
    public function where(): array
    {
        $conditions = [];
        $params = [];
        if ($this->typ !== null) {
            $conditions[] = 'k.typ = ?';
            $params[] = $this->typ;
        }
        if ($this->newsletter === 'ja') {
            $conditions[] = 'EXISTS (' . self::NEWSLETTER_ACTIVE . ')';
        } elseif ($this->newsletter === 'nein') {
            $conditions[] = 'NOT EXISTS (' . self::NEWSLETTER_ACTIVE . ')';
        }
        if ($this->ort !== null) {
            $conditions[] = 'k.ort = ?';
            $params[] = $this->ort;
        }
        if ($this->status === self::STATUS_NONE) {
            $conditions[] = 'k.status IS NULL';
        } elseif ($this->status !== null) {
            $conditions[] = 'k.status = ?';
            $params[] = $this->status;
        }
        if ($this->q !== '') {
            $like = '%' . addcslashes($this->q, '%_\\') . '%';
            $conditions[] = "(k.name LIKE ? OR k.email LIKE ? OR k.ort LIKE ? OR k.vorname LIKE ? OR k.nachname LIKE ?
                OR CONCAT_WS(' ', k.vorname, k.nachname) LIKE ?
                OR EXISTS (SELECT 1 FROM ansprechpartner a WHERE a.kunde_id = k.id
                           AND (a.vorname LIKE ? OR a.nachname LIKE ? OR CONCAT_WS(' ', a.vorname, a.nachname) LIKE ? OR a.email LIKE ?))
                OR EXISTS (SELECT 1 FROM kinder ki WHERE ki.kunde_id = k.id
                           AND (ki.vorname LIKE ? OR CONCAT_WS(' ', ki.vorname, ki.nachname) LIKE ?)))";
            array_push($params, ...array_fill(0, 12, $like));
        }
        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}
