<?php

namespace FireflyIII\Http\Controllers;


use Crypt;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use View;

/**
 * Class AttachmentController
 *
 * @package FireflyIII\Http\Controllers
 */
class AttachmentController extends Controller
{

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        View::share('mainTitleIcon', 'fa-paperclip');
        View::share('title', trans('firefly.attachments'));
    }


    /**
     * @param Attachment $attachment
     *
     * @return \Illuminate\View\View
     */
    public function edit(Attachment $attachment)
    {
        $subTitleIcon = 'fa-pencil';
        $subTitle     = trans('firefly.edit_attachment', ['name' => $attachment->filename]);

        return view('attachments.edit', compact('attachment', 'subTitleIcon', 'subTitle'));
    }

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