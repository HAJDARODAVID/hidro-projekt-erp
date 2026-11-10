<?php

namespace App\Services\Application;

use Illuminate\Support\Facades\File;

/**
 * Class CustomizeService.
 * Reads the per company GUI customizations from the customize folder:
 *
 *   customize/{company}/{name}.json
 *
 * The database always holds every field; these files only decide what a screen shows.
 * A file that doesn't exist means "no customization" (show everything).
 */
class CustomizeService
{
    /**
     * The active company (= folder name).
     * Today from APP_COMPANY in .env (config/customize.php), later from the company table:
     * this is the only place that has to change for that.
     *
     * @return string
     */
    public static function currentCompany(): string
    {
        return (string) config('customize.company', 'hidro-projekt');
    }

    /**
     * Folder of a company (default: the active one).
     *
     * @param string|null $company
     * @return string
     */
    public static function companyPath(?string $company = NULL): string
    {
        $company = $company ?? self::currentCompany();
        // Only plain folder names, so a company name can never point outside the folder
        if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/i', $company)) throw new \InvalidArgumentException("Invalid company folder name: $company");

        return rtrim((string) config('customize.path', base_path('customize')), '/\\') . DIRECTORY_SEPARATOR . $company;
    }

    /**
     * Path of a customization file.
     *
     * @param string $name file name without .json, e.g. "workplace_fields"
     * @param string|null $company
     * @return string
     */
    public static function filePath(string $name, ?string $company = NULL): string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/i', $name)) throw new \InvalidArgumentException("Invalid customization name: $name");
        return self::companyPath($company) . DIRECTORY_SEPARATOR . $name . '.json';
    }

    /**
     * The customization, NULL when the company has no such file.
     *
     * @param string $name e.g. "workplace_fields"
     * @param string|null $company default: the active company
     * @return array|null
     * @throws \RuntimeException when the file isn't valid JSON (a broken file must not silently show everything)
     */
    public static function get(string $name, ?string $company = NULL): ?array
    {
        $path = self::filePath($name, $company);
        if (!File::exists($path)) return NULL;

        $data = json_decode(File::get($path), TRUE);
        if (!is_array($data)) {
            throw new \RuntimeException('Customization file is not valid JSON: ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path) . ' (' . json_last_error_msg() . ')');
        }

        unset($data['$comment']);
        return $data;
    }

    /**
     * Whether the company has the customization file.
     */
    public static function has(string $name, ?string $company = NULL): bool
    {
        return File::exists(self::filePath($name, $company));
    }
}
