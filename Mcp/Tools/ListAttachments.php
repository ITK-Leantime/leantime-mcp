<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists a todo's attachments.
 */
class ListAttachments
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Lists the files attached to a todo — filename, type, who uploaded it and when. File
     * contents are not available through this server; open the todo in Leantime to download.
     *
     * @param  int  $todoId  Todo whose attachments to list.
     * @return list<mixed> Attachment metadata.
     */
    public function __invoke(int $todoId): array
    {
        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->ticketFiles($todoId, $apiUser)
        );
    }
}
