<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Services;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;

class ProductionSshService
{
    protected ?SSH2 $connection = null;

    protected string $host;

    protected string $user;

    protected string $deployPath;

    protected string $phpBinary;

    protected string $privateKeyPath;

    public function __construct()
    {
        $this->host = config('northcloud.mcp.production.host', '');
        $this->user = config('northcloud.mcp.production.user', 'deployer');
        $this->deployPath = config('northcloud.mcp.production.deploy_path', '');
        $this->phpBinary = config('northcloud.mcp.production.php_binary', 'php');
        $this->privateKeyPath = config('northcloud.mcp.production.private_key_path', '~/.ssh/id_ed25519');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->host) && ! empty($this->deployPath);
    }

    public function connect(): SSH2
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('Production SSH is not configured. Set NORTHCLOUD_PRODUCTION_HOST and NORTHCLOUD_PRODUCTION_PATH.');
        }

        $keyPath = str_replace('~', $_SERVER['HOME'] ?? getenv('HOME'), $this->privateKeyPath);

        if (! file_exists($keyPath)) {
            throw new RuntimeException("SSH private key not found at: {$keyPath}");
        }

        $key = PublicKeyLoader::load(file_get_contents($keyPath));

        $this->connection = new SSH2($this->host);

        if (! $this->connection->login($this->user, $key)) {
            throw new RuntimeException("SSH authentication failed for {$this->user}@{$this->host}");
        }

        return $this->connection;
    }

    public function disconnect(): void
    {
        if ($this->connection !== null) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    /**
     * Run a command on the production server via SSH.
     *
     * @return array{stdout: string, stderr: string, exit_code: int}
     */
    public function runCommand(string $command): array
    {
        $ssh = $this->connect();

        $ssh->enableQuietMode();
        $stdout = $ssh->exec($command);
        $stderr = $ssh->getStdError();
        $exitCode = $ssh->getExitStatus();

        return [
            'stdout' => $stdout !== false ? $stdout : '',
            'stderr' => $stderr !== false ? $stderr : '',
            'exit_code' => is_int($exitCode) ? $exitCode : -1,
        ];
    }

    /**
     * Run a command in the deploy path directory.
     *
     * @return array{stdout: string, stderr: string, exit_code: int}
     */
    public function runInDeployPath(string $command): array
    {
        $fullCommand = "cd {$this->deployPath}/current && {$command}";

        return $this->runCommand($fullCommand);
    }

    /**
     * Run an artisan command on production.
     *
     * @return array{stdout: string, stderr: string, exit_code: int}
     */
    public function artisan(string $command, string $arguments = ''): array
    {
        $fullCommand = "{$this->phpBinary} artisan {$command}";
        if (! empty($arguments)) {
            $fullCommand .= ' '.$arguments;
        }

        return $this->runInDeployPath($fullCommand);
    }

    /**
     * Query the production database using tinker (database-agnostic).
     *
     * @return array{stdout: string, stderr: string, exit_code: int}
     */
    public function dbQuery(string $query): array
    {
        $escapedQuery = str_replace("'", "\\'", $query);
        $tinkerCode = "echo json_encode(DB::select('{$escapedQuery}'));";

        return $this->tinker($tinkerCode);
    }

    /**
     * Run PHP code via artisan tinker on production.
     *
     * @return array{stdout: string, stderr: string, exit_code: int}
     */
    public function tinker(string $code): array
    {
        $escapedCode = escapeshellarg($code);
        $command = "{$this->phpBinary} artisan tinker --execute={$escapedCode}";

        return $this->runInDeployPath($command);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getDeployPath(): string
    {
        return $this->deployPath;
    }
}
