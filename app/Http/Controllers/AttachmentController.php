<?php
/**
 * AttachmentController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Crypt;
use File;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Requests\AttachmentFormRequest;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Input;
use Log;
use Preferences;
use Response;
use Session;
use Storage;
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
     *
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
     * @return View
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

        Session::flash('success', strval(trans('firefly.attachment_deleted', ['name' => $name])));
        Preferences::mark();

        return redirect(session('attachments.delete.url'));
    }

    /**
     * @param Attachment $attachment
     *
     * @throws FireflyException
     *
     */
    public function download(Attachment $attachment)
    {
        // create a disk.
        $disk = Storage::disk('upload');
        $file = $attachment->fileName();

        if ($disk->exists($file)) {

            $quoted  = sprintf('"%s"', addcslashes(basename($attachment->filename), '"\\'));
            $content = Crypt::decrypt($disk->get($file));

            Log::debug('Send file to user', ['file' => $quoted, 'size' => strlen($content)]);

            return response($content, 200)
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename=' . $quoted)
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Connection', 'Keep-Alive')
                ->header('Expires', '0')
                ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'public')
                ->header('Content-Length', strlen($content));

        }
        throw new FireflyException('Could not find the indicated attachment. The file is no longer there.');
    }

    /**
     * @param Attachment $attachment
     *
     * @return View
     */
    public function edit(Attachment $attachment)
    {
        $subTitleIcon = 'fa-pencil';
        $subTitle     = trans('firefly.edit_attachment', ['name' => $attachment->filename]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('attachments.edit.fromUpdate') !== true) {
            Session::put('attachments.edit.url', URL::previous());
        }
        Session::forget('attachments.edit.fromUpdate');

        return view('attachments.edit', compact('attachment', 'subTitleIcon', 'subTitle'));
    }

    /**
     * @param Attachment $attachment
     *
     * @return \Illuminate\Http\Response
     */
    public function preview(Attachment $attachment)
    {
        $image = 'images/page_green.png';


        if ($attachment->mime == 'application/pdf') {
            $image = 'images/page_white_acrobat.png';
        }
        $file     = public_path($image);
        $response = Response::make(File::get($file));
        $response->header('Content-Type', 'image/png');

        return $response;
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

        Session::flash('success', strval(trans('firefly.attachment_updated', ['name' => $attachment->filename])));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('attachments.edit.fromUpdate', true);

            return redirect(route('attachments.edit', [$attachment->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('attachments.edit.url'));

    }

}
