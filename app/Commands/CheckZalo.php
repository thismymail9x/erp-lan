<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckZalo extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'App';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'zalo:refresh-token';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Kiem tra va lam moi Zalo OA access token neu sap het han.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'zalo:refresh-token';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $service = new \App\Services\ZaloService();
        $tokens = $service->getValidTokens(3600);

        if (isset($tokens['error'])) {
            CLI::error('Khong the lam moi Zalo token: ' . ($tokens['message'] ?? $tokens['error']));
            return;
        }

        if (empty($tokens['access_token'])) {
            CLI::error('Khong co Zalo access token. Can ket noi lai Zalo OA.');
            return;
        }

        CLI::write('Zalo OA token hop le.', 'green');
    }
}
