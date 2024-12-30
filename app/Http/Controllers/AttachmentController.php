<?php

/**
 * AttachmentController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Requests\AttachmentFormRequest;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class AttachmentController.
 */
class AttachmentController extends Controller
{
    private AttachmentRepositoryInterface $repository;

    /**
     * AttachmentController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paperclip');
                app('view')->share('title', (string) trans('firefly.attachments'));
                $this->repository = app(AttachmentRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Form to delete an attachment.
     *
     * @return Factory|View
     */
    public function delete(Attachment $attachment)
    {
        $subTitle = (string) trans('firefly.delete_attachment', ['name' => $attachment->filename]);

        // put previous url in session
        $this->rememberPreviousUrl('attachments.delete.url');

        return view('attachments.delete', compact('attachment', 'subTitle'));
    }

    /**
     * Destroy attachment.
     *
     * @return Redirector|RedirectResponse
     */
    public function destroy(Request $request, Attachment $attachment)
    {
        $name = $attachment->filename;

        $this->repository->destroy($attachment);

        $request->session()->flash('success', (string) trans('firefly.attachment_deleted', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUrl('attachments.delete.url'));
    }

    /**
     * Download attachment to PC.
     *
     * @return LaravelResponse
     *
     * @throws FireflyException
     */
    public function download(Attachment $attachment)
    {
        if ($this->repository->exists($attachment)) {
            $content  = $this->repository->getContent($attachment);
            $quoted   = sprintf('"%s"', addcslashes(basename($attachment->filename), '"\\'));

            /** @var LaravelResponse $response */
            $response = response($content);
            $response
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename='.$quoted)
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Connection', 'Keep-Alive')
                ->header('Expires', '0')
                ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'public')
                ->header('Content-Length', (string) strlen($content))
            ;

            return $response;
        }

        throw new FireflyException('Could not find the indicated attachment. The file is no longer there.');
    }

    /**
     * Edit an attachment.
     *
     * @return Factory|View
     */
    public function edit(Request $request, Attachment $attachment)
    {
        $subTitleIcon = 'fa-pencil';
        $subTitle     = (string) trans('firefly.edit_attachment', ['name' => $attachment->filename]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('attachments.edit.fromUpdate')) {
            $this->rememberPreviousUrl('attachments.edit.url');
        }
        $request->session()->forget('attachments.edit.fromUpdate');
        $preFilled    = [
            'notes' => $this->repository->getNoteText($attachment),
        ];
        $request->session()->flash('preFilled', $preFilled);

        return view('attachments.edit', compact('attachment', 'subTitleIcon', 'subTitle'));
    }

    /**
     * Index of all attachments.
     *
     * @return Factory|View
     */
    public function index()
    {
        $set = $this->repository->get()->reverse();
        $set = $set->each(
            function (Attachment $attachment) {
                $attachment->file_exists = $this->repository->exists($attachment);

                return $attachment;
            }
        );

        return view('attachments.index', compact('set'));
    }

    /**
     * Update attachment.
     */
    public function update(AttachmentFormRequest $request, Attachment $attachment): RedirectResponse
    {
        $data     = $request->getAttachmentData();
        $this->repository->update($attachment, $data);

        $request->session()->flash('success', (string) trans('firefly.attachment_updated', ['name' => $attachment->filename]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUrl('attachments.edit.url'));
        if (1 === (int) $request->get('return_to_edit')) {
            $request->session()->put('attachments.edit.fromUpdate', true);

            $redirect = redirect(route('attachments.edit', [$attachment->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return $redirect;
    }

    /**
     * View attachment in browser.
     *
     * @throws FireflyException
     * @throws BindingResolutionException
     */
    public function view(Attachment $attachment): LaravelResponse
    {
        if ($this->repository->exists($attachment)) {
            $content = $this->repository->getContent($attachment);

            // prevent XSS by adding a new secure header.
            $csp     = [
                "default-src 'none'",
                "object-src 'none'",
                "script-src 'none'",
                "style-src 'self' 'unsafe-inline'",
                "base-uri 'none'",
                "font-src 'none'",
                "connect-src 'none'",
                "img-src 'self'",
                "manifest-src 'none'",
            ];

            return response()->make(
                $content,
                200,
                [
                    'Content-Security-Policy' => implode('; ', $csp),
                    'Content-Type'            => $attachment->mime,
                    'Content-Disposition'     => 'inline; filename="'.$attachment->filename.'"',
                ]
            );
        }

        throw new FireflyException('Could not find the indicated attachment. The file is no longer there.');
    }
}
