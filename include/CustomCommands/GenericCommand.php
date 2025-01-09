<?php
# @Author: amirhp-com <its@amirhp.com>
# @Date:   2022/10/12 18:46:10
# @Last modified by:   amirhp-com <its@amirhp.com>
# @Last modified time: 2022/10/13 01:56:32
# Generic command Gets executed for generic commands, when no other appropriate one is found.
namespace Longman\TelegramBot\Commands\SystemCommands;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
class GenericCommand extends SystemCommand
{
  protected $name = 'generic';
  protected $description = 'Handles generic commands or is executed by default when a command is not found';
  protected $version = '1.1.0';
  public function execute(): ServerResponse
  {
    $message = $this->getMessage();
    $command = is_object($message) ? (property_exists($message, "getCommand") ? $message->getCommand() : "") : "";
    return $this->replyToChat("_Command /{$command} not found 🤷‍♂️\nSend /start to get help_", array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => is_object($message) ? (property_exists($message, "getMessageId") ? $message->getMessageId() : "") : "",
      "protect_content"          => false,
      "disable_web_page_preview" => true,
    ));
  }
}
