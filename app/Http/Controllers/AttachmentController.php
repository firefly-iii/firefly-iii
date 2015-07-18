<?php

namespace FireflyIII\Http\Controllers;


use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use Crypt;
/**
 * Class AttachmentController
 *
 * @package FireflyIII\Http\Controllers
 */
class AttachmentController extends Controller
{

    /**
     * @param Attachment $attachment
     */
    public function download(Attachment $attachment, AttachmentHelperInterface $helper)
    {

        $file = $helper->getAttachmentLocation($attachment);
        if (file_exists($file)) {

            $quoted = sprintf('"%s"', addcslashes(basename($attachment->filename), '"\\'));

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $quoted);
            header('Content-Transfer-Encoding: binary');
            header('Connection: Keep-Alive');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $attachment->size);

            echo Crypt::decrypt(file_get_contents($file));

        } else {
            abort(404);
        }


    }

}