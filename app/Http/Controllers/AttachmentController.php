<?php

namespace FireflyIII\Http\Controllers;


use Crypt;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Requests\AttachmentFormRequest;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Input;
use Preferences;
use Session;
use URL;
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

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('attachments.edit.fromUpdate') !== true) {
            Session::put('attachments.edit.url', URL::previous());
        }
        Session::forget('attachments.edit.fromUpdate');

        return view('attachments.edit', compact('attachment', 'subTitleIcon', 'subTitle'));
    }

    /**
     * @param Attachment $attachment
     *
     * @return \Illuminate\View\View
     */
    public function delete(Attachment $attachment)
    {
        $subTitle = trans('firefly.delete_attachment', ['name' => $attachment->filename]);

        // put previous url in session
        Session::put('attachments.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'attachments');
        Session::flash('gaEventAction', 'delete-attachment');

        return view('attachments.delete', compact('attachment', 'subTitle'));
    }

    /**
     * @param AttachmentRepositoryInterface $repository
     * @param Attachment                    $attachment
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AttachmentRepositoryInterface $repository, Attachment $attachment)
    {
        $name = $attachment->filename;

        $repository->destroy($attachment);

        Session::flash('success', trans('firefly.attachment_deleted', ['name' => $name]));
        Preferences::mark();

        return redirect(Session::get('attachments.delete.url'));
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

    /**
     * @param Attachment $attachment
     */
    public function preview(AttachmentHelperInterface $helper, Attachment $attachment)
    {
        if (!function_exists('imagecreatetruecolor')) {
            abort(500);
        }


        $mime  = $attachment->mime;
        $cache = new CacheProperties;
        $cache->addProperty('preview-attachment');
        $cache->addProperty($attachment->id);
        if ($cache->has()) {
            header('Content-Type: image/png');

            return $cache->get();
        }

        switch ($mime) {
            default:
                $img = imagecreatetruecolor(100, 10);
                imagesavealpha($img, true);

                $trans_colour = imagecolorallocatealpha($img, 0, 0, 0, 127);
                imagefill($img, 0, 0, $trans_colour);
                header('Content-Type: image/png');
                imagepng($img);
                imagedestroy($img);
                $cache->store($img);
                exit;
                break;
            case 'image/jpeg':
            case 'image/png':
                $img      = imagecreatetruecolor(100, 100);
                $source   = $helper->getAttachmentLocation($attachment);
                $decrypt  = Crypt::decrypt(file_get_contents($source));
                $original = imagecreatefromstring($decrypt);
                $width    = imagesx($original);
                $height   = imagesy($original);

                imagecopyresampled($img, $original, 0, 0, 0, 0, 100, 100, $width, $height);

                header('Content-Type: image/png');
                imagepng($img);
                imagedestroy($img);
                exit;

                break;
        }
    }


    /**
     * @param AttachmentFormRequest         $request
     * @param AttachmentRepositoryInterface $repository
     * @param Attachment                    $attachment
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AttachmentFormRequest $request, AttachmentRepositoryInterface $repository, Attachment $attachment)
    {

        $attachmentData = [
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'notes'       => $request->input('notes'),
        ];

        $repository->update($attachment, $attachmentData);

        Session::flash('success', 'Attachment "' . $attachment->filename . '" updated.');
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('attachments.edit.fromUpdate', true);

            return redirect(route('attachments.edit', [$attachment->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(Session::get('attachments.edit.url'));

    }

}
