/*
 * drag-and-droppable-rows.js
 * Copyright (c) 2026 james@firefly-iii.org
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

export function addDrag() {
    const tbody = document.querySelector(".sortable tbody");
    let draggedRow = null;

    // animation Helper
    function getRowOffsets() {
        const map = new Map();
        tbody.querySelectorAll("tr").forEach((row) => {
            map.set(row, row.getBoundingClientRect().top);
        });
        return map;
    }

    // swap logic
    function handleRowSwap(clientY, targetElement) {
        const targetRow = targetElement.closest("tr");

        if (
            targetRow &&
            targetRow !== draggedRow &&
            targetRow.parentNode === tbody
        ) {
            const rect = targetRow.getBoundingClientRect();
            const next = (clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
            const nextSibling = next ? targetRow.nextSibling : targetRow;

            if (draggedRow.nextSibling !== nextSibling) {
                const firstPositions = getRowOffsets();

                tbody.insertBefore(draggedRow, nextSibling);

                const lastPositions = getRowOffsets();

                tbody.querySelectorAll("tr").forEach((row) => {
                    const firstTop = firstPositions.get(row);
                    const lastTop = lastPositions.get(row);
                    const deltaY = firstTop - lastTop;

                    if (deltaY !== 0) {
                        row.style.transform = `translateY(${deltaY}px)`;
                        row.style.transition = "none";

                        requestAnimationFrame(() => {
                            row.style.transform = "";
                            row.style.transition = "transform 0.2s ease-out";
                        });
                    }
                });
            }
        }
    }

    function getRowOrder() {
        let index = 1;
        return Array.from(tbody.querySelectorAll("tr")).map((row) => ({
            id: row.getAttribute("data-id"),
            order: index++,
            currentOrder: parseInt(row.getAttribute("data-current-order")),
            page: parseInt(row.getAttribute("data-page")),
        }));
    }

    // add events
    tbody.addEventListener("mousedown", (e) => {
        if (e.target.classList.contains("object-handle")) {
            e.target.closest("tr").setAttribute("draggable", "true");
        }
    });

    tbody.addEventListener("dragstart", (e) => {
        draggedRow = e.target.closest("tr");
        setTimeout(() => draggedRow.classList.add("is-dragging"), 0);
    });

    tbody.addEventListener("dragend", () => {
        if (draggedRow) {
            draggedRow.removeAttribute("draggable");
            draggedRow.classList.remove("is-dragging");
            draggedRow = null;
            const currentOrder = getRowOrder();
            const event = new CustomEvent("firefly-iii-drag-complete", {
                detail: currentOrder,
            });
            document.dispatchEvent(event);
        }
    });

    tbody.addEventListener("dragover", (e) => {
        e.preventDefault();
        handleRowSwap(e.clientY, e.target);
    });

    tbody.addEventListener(
        "touchstart",
        (e) => {
            if (e.target.classList.contains("object-handle")) {
                draggedRow = e.target.closest("tr");
                draggedRow.classList.add("is-dragging");
            }
        },
        { passive: true },
    );

    tbody.addEventListener(
        "touchmove",
        (e) => {
            if (!draggedRow) return;

            // Get finger coordinates
            const touch = e.touches[0];

            // Find what element is currently underneath the user's finger
            const elementUnderFinger = document.elementFromPoint(
                touch.clientX,
                touch.clientY,
            );

            if (elementUnderFinger) {
                handleRowSwap(touch.clientY, elementUnderFinger);
            }
        },
        { passive: true },
    );

    tbody.addEventListener("touchend", () => {
        if (draggedRow) {
            draggedRow.classList.remove("is-dragging");
            draggedRow = null;
            const currentOrder = getRowOrder();
            const event = new CustomEvent("firefly-iii-drag-complete", {
                detail: currentOrder,
            });
            document.dispatchEvent(event);
        }
    });
}
