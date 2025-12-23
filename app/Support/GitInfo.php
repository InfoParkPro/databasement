<?php

namespace App\Support;

class GitInfo
{
    /**
     * Get the current git commit hash (short version).
     * Uses APP_COMMIT_HASH env var if set (for deployed builds),
     * otherwise tries to read from local git repository.
     */
    public static function getCommitHash(): ?string
    {
        // First check for configured commit hash (set during build/deployment)
        $configuredHash = config('app.commit_hash');
        if ($configuredHash) {
            return substr($configuredHash, 0, 7);
        }

        // Try to get from local git repository
        $gitHeadFile = base_path('.git/HEAD');
        if (! file_exists($gitHeadFile)) {
            return null;
        }

        $headContent = file_get_contents($gitHeadFile);
        if ($headContent === false) {
            return null;
        }
        $head = trim($headContent);

        // Check if HEAD is a direct commit hash or a reference
        if (str_starts_with($head, 'ref: ')) {
            $refPath = base_path('.git/'.substr($head, 5));
            if (file_exists($refPath)) {
                $refContent = file_get_contents($refPath);
                if ($refContent === false) {
                    return null;
                }
                $hash = trim($refContent);

                return substr($hash, 0, 7);
            }

            return null;
        }

        // HEAD is a direct commit hash
        return substr($head, 0, 7);
    }

    /**
     * Get the full commit hash.
     */
    public static function getFullCommitHash(): ?string
    {
        $configuredHash = config('app.commit_hash');
        if ($configuredHash) {
            return $configuredHash;
        }

        $gitHeadFile = base_path('.git/HEAD');
        if (! file_exists($gitHeadFile)) {
            return null;
        }

        $headContent = file_get_contents($gitHeadFile);
        if ($headContent === false) {
            return null;
        }
        $head = trim($headContent);

        if (str_starts_with($head, 'ref: ')) {
            $refPath = base_path('.git/'.substr($head, 5));
            if (file_exists($refPath)) {
                $refContent = file_get_contents($refPath);
                if ($refContent === false) {
                    return null;
                }

                return trim($refContent);
            }

            return null;
        }

        return $head;
    }

    /**
     * Get the GitHub repository URL.
     */
    public static function getGitHubRepo(): string
    {
        return config('app.github_repo', 'https://github.com/David-Crty/databasement');
    }

    /**
     * Get the URL to a specific commit on GitHub.
     */
    public static function getCommitUrl(): ?string
    {
        $fullHash = self::getFullCommitHash();
        if (! $fullHash) {
            return null;
        }

        return self::getGitHubRepo().'/commit/'.$fullHash;
    }

    /**
     * Get the URL to open a new issue on GitHub.
     */
    public static function getNewIssueUrl(): string
    {
        return self::getGitHubRepo().'/issues/new';
    }
}
