<?php
/**
 * AttachmentController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

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
 * Class AttachmentController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // it's 13.
 */
class AttachmentController extends Controller
{
    /** @var AttachmentRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paperclip');
                app('view')->share('title', trans('firefly.attachments'));
                $this->repository = app(AttachmentRepositoryInterface::class);

                return $next($request);
            }
        );
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
        $this->rememberPreviousUri('attachments.delete.uri');

        return view('attachments.delete', compact('attachment', 'subTitle'));
    }

    /**
     * @param Request    $request
     * @param Attachment $attachment
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, Attachment $attachment)
    {
        $name = $attachment->filename;

        $this->repository->destroy($attachment);

        $request->session()->flash('success', strval(trans('firefly.attachment_deleted', ['name' => $name])));
        Preferences::mark();

        return redirect($this->getPreviousUri('attachments.delete.uri'));
    }

    /**
     * @param Attachment $attachment
     *
     * @return mixed
     *
     * @throws FireflyException
     */
    public function download(Attachment $attachment)
    {
        if ($this->repository->exists($attachment)) {
            $content = $this->repository->getContent($attachment);
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
        if (true !== session('attachments.edit.fromUpdate')) {
            $this->rememberPreviousUri('attachments.edit.uri');
        }
        $request->session()->forget('attachments.edit.fromUpdate');

        return view('attachments.edit', compact('attachment', 'subTitleIcon', 'subTitle'));
    }

    /**
     * @param AttachmentFormRequest $request
     * @param Attachment            $attachment
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AttachmentFormRequest $request, Attachment $attachment)
    {
        $data = $request->getAttachmentData();
        $this->repository->update($attachment, $data);

        $request->session()->flash('success', strval(trans('firefly.attachment_updated', ['name' => $attachment->filename])));
        Preferences::mark();

        if (1 === intval($request->get('return_to_edit'))) {
            // @codeCoverageIgnoreStart
            $request->session()->put('attachments.edit.fromUpdate', true);

            return redirect(route('attachments.edit', [$attachment->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('attachments.edit.uri'));
    }

    /**
     * @param Attachment $attachment
     *
     * @return \Illuminate\Http\Response
     * @throws FireflyException
     */
    public function view(Attachment $attachment)
    {
        if ($this->repository->exists($attachment)) {
            $content = $this->repository->getContent($attachment);

            return Response::make(
                $content, 200, [
                            'Content-Type'        => $attachment->mime,
                            'Content-Disposition' => 'inline; filename="' . $attachment->filename . '"',
                        ]
            );
        }
        throw new FireflyException('Could not find the indicated attachment. The file is no longer there.');
    }
}
