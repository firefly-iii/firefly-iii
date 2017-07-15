<?php
/**
 * AttachmentController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use File;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Requests\AttachmentFormRequest;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Preferences;
use Response;
use View;

/**
 * Class AttachmentController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // it's 13.
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

        // translations:
        $this->middleware(
            function ($request, $next) {
                View::share('mainTitleIcon', 'fa-paperclip');
                View::share('title', trans('firefly.attachments'));

                return $next($request);
            }
        );
    }

    /**
     * @param Request    $request
     * @param Attachment $attachment
     *
     * @return View
     */
    public function delete(Request $request, Attachment $attachment)
    {
        $subTitle = trans('firefly.delete_attachment', ['name' => $attachment->filename]);

        // put previous url in session
        $this->rememberPreviousUri('attachments.delete.uri');
        $request->session()->flash('gaEventCategory', 'attachments');
        $request->session()->flash('gaEventAction', 'delete-attachment');

        return view('attachments.delete', compact('attachment', 'subTitle'));
    }

    /**
     * @param Request                       $request
     * @param AttachmentRepositoryInterface $repository
     * @param Attachment                    $attachment
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, AttachmentRepositoryInterface $repository, Attachment $attachment)
    {
        $name = $attachment->filename;

        $repository->destroy($attachment);

        $request->session()->flash('success', strval(trans('firefly.attachment_deleted', ['name' => $name])));
        Preferences::mark();

        return redirect($this->getPreviousUri('attachments.delete.uri'));
    }

    /**
     * @param AttachmentRepositoryInterface $repository
     * @param Attachment                    $attachment
     *
     * @return mixed
     * @throws FireflyException
     */
    public function download(AttachmentRepositoryInterface $repository, Attachment $attachment)
    {


        if ($repository->exists($attachment)) {
            $content = $repository->getContent($attachment);
            $quoted  = sprintf('"%s"', addcslashes(basename($attachment->filename), '"\\'));


            /** @var LaravelResponse $response */
            $response = response($content, 200);
            $response
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename=' . $quoted)
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Connection', 'Keep-Alive')
                ->header('Expires', '0')
                ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'public')
                ->header('Content-Length', strlen($content));

            return $response;
        }
        throw new FireflyException('Could not find the indicated attachment. The file is no longer there.');
    }

    /**
     * @param Request    $request
     * @param Attachment $attachment
     *
     * @return View
     */
    public function edit(Request $request, Attachment $attachment)
    {
        $subTitleIcon = 'fa-pencil';
        $subTitle     = trans('firefly.edit_attachment', ['name' => $attachment->filename]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('attachments.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('attachments.edit.uri');
        }
        $request->session()->forget('attachments.edit.fromUpdate');

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


        if ($attachment->mime === 'application/pdf') {
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
        $data = $request->getAttachmentData();
        $repository->update($attachment, $data);

        $request->session()->flash('success', strval(trans('firefly.attachment_updated', ['name' => $attachment->filename])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // @codeCoverageIgnoreStart
            $request->session()->put('attachments.edit.fromUpdate', true);

            return redirect(route('attachments.edit', [$attachment->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('attachments.edit.uri'));

    }

}
