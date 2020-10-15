<?php

namespace Webklex\IMAP\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client as ClientFacade;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;

class ImapIdleCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imap:idle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch new messages by utilising imap idle';

    /**
     * Holds the account information
     *
     * @var string|array $account
     */
    protected $account = "default";

    /**
     * Name of the used folder
     *
     * @var string $folder_name
     */
    protected $folder_name = "INBOX";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Callback used for the idle command and triggered for every new received message
     * @param Message $message
     */
    public function onNewMessage(Message $message){
        $this->info("New message received: ".$message->subject);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        if (is_array($this->account)) {
            $client = ClientFacade::make($this->account);
        }else{
            $client = ClientFacade::account($this->account);
        }

        try {
            $client->connect();
        } catch (ConnectionFailedException $e) {
            Log::error($e->getMessage());
            return 1;
        }

        /** @var Folder $folder */
        try {
            $folder = $client->getFolder($this->folder_name);
        } catch (ConnectionFailedException $e) {
            Log::error($e->getMessage());
            return 1;
        } catch (FolderFetchingException $e) {
            Log::error($e->getMessage());
            return 1;
        }

        try {
            $folder->idle(function($message){
                $this->onNewMessage($message);
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
