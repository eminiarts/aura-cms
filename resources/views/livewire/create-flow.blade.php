<div class="">


<style >
    .flows:before {
        position: absolute;
        top: -4px;
        left: -4px;
        display: block;
        width: calc(100% + 8px);
        height: calc(100% + 8px);
        background-image: radial-gradient(rgb(var(--gray-500)) 10%,transparent 10%);
        background-position: -6px -6px;
        background-size: 20px 20px;
        opacity: 0.2;
        content: "";
        pointer-events: none;
    }

    .operation {
        --height: 10;
        --width: 10;
        grid-row: var(--pos-y)/span var(--height);
        grid-column: var(--pos-x)/span var(--width);
    }

    .flows path {
        stroke: var(--gray-500);
        stroke-width: 2;
        fill: none;
    }

    .flows path.resolve {
        stroke: rgb(var(--primary-400));
    }
    .flows path.reject {
        stroke: rgb(217, 88, 112);
    }

    .flows {
        min-height: 700px;
        height: calc(100vh - 300px);
    }

    .selected {
        border-color: var(--gray-500);
    }
</style>

{{-- @dump($flow->toArray()) --}}

<div class="overflow-x-auto overflow-y-hidden w-full bg-gray-50 rounded-xl border-2 border-gray-500/30">
    <div x-data="flow"
    class="w-full relative grid text-gray-900 grid-rows-[repeat(auto-fill,20px)] grid-cols-[repeat(auto-fill,20px)] flows" x-ref="flows" @mousemove="moveOperation($event)" @mouseup="cancelMove($event)"  :style="{'width' : canvasWidth + 'px', 'height' : canvasHeight + 'px' }">

    <!-- position absoult, svg -->
    <div class="absolute top-0 left-0 w-full h-full">
        <svg class="w-full h-full">

            <g x-html="constructArrows()">

            </g>

            <g x-html="mouseArrow"></g>

        </svg>
    </div>



    <div
        x-cloak
        class="relative col-span-8 p-4 bg-white rounded-lg border-2 shadow-md cursor-move select-none border-primary-400 row-span-9 operation" :data-operation="flow.id"

        :style="{ '--pos-x': 2, '--pos-y': 2 }"

        :class="{'selected': selectedOperation === flow }"
    >

        <div class="flex justify-between">
            <div class="text-sm font-semibold leading-tight select-text" x-text="flow.name"></div>
        </div>

        <div class="flex flex-col text-xs">
            <label class="mt-2 font-semibold text-gray-900">Trigger</label>
            <div class="text-gray-500 select-text" x-text="flow.trigger"></div>

            <template x-if="flow.options && flow.options.resource">
                <label class="mt-2 font-semibold text-gray-900">Resource</label>
                <div class="text-gray-500 select-text" x-text="flow.options.resource"></div>
            </template>

            <template x-if="flow.options && flow.options.event">
                <label class="mt-2 font-semibold text-gray-900">Event</label>
                <div class="text-gray-500 select-text" x-text="flow.options.event"></div>
            </template>

            <label class="mt-2 font-semibold text-gray-900">Status</label>
            <div class="text-gray-500 select-text" x-text="flow.status"></div>

        </div>

        <template x-if="flow.operation_id">
            <div
                class="absolute top-[30px] right-[-0.5rem] connect-resolve"
                @mousedown="connectFlowResolve($event)"
                >
                <div class="w-4 h-4 bg-white rounded-full border-2 border-red-500"></div>
            </div>
        </template>
        <template x-if="!flow.operation_id">
            <div>
                <div
                x-data x-ref="this" x-init="tippy($refs.this, { content: 'Drag to operation to reroute', arrow: false, theme: 'aura', placement: 'top', offset: [0, 8], })"
                class="absolute top-[30px] right-[-0.5rem] connect-resolve"
                @mousedown="connectFlowResolve($event)"
                >
                <div class="w-4 h-4 bg-white rounded-full border-2 border-primary-400"></div>
            </div>
            <div
                x-data x-ref="this" x-init="tippy($refs.this, { content: 'Click to add operation', arrow: false, theme: 'aura', placement: 'top', offset: [0, 8], })"
                class="absolute top-[30px] right-[-2rem] connect-resolve group cursor-pointer"
                @mousedown="addResolveFlow($event)"
                >

                <div class="flex justify-center items-center w-4 h-4 font-semibold leading-none text-gray-300 bg-white rounded-full border-2 border-gray-500/30 group-hover:border-primary-400 group-hover:text-primary-500"> <span class="inline-block mt-[-1px]">+</span> </div>
            </div>
            </div>
        </template>




    </div>

    <!-- if there are operations, show them  -->
    <template x-if="operations.length > 0">

        <template x-for="operation in operations" :key="operation.id">
            <div
                x-cloak
                class="relative col-span-8 p-4 bg-white rounded-lg border-2 shadow-md cursor-move select-none border-gray-500/30 row-span-9 operation" :data-operation="operation.id"

                :style="{ '--pos-x': operation.options.x, '--pos-y': operation.options.y }"

                :class="{'selected': selectedOperation === operation }"
                @mousedown="mouseDown($event, operation)"
            >


            <div class="flex justify-between">
                <div class="text-sm font-semibold leading-tight select-text" x-text="operation.name"></div>

                <div x-data x-ref="this" x-init="tippy($refs.this, { content: 'Edit operation', arrow: false, theme: 'aura', placement: 'top', offset: [0, 8], })">
                    <x-aura::button.border size="xs" @mousedown="$event.stopPropagation()" @click="selectOperation(operation)">
                        <x-aura::icon class="pointer-events-none" icon="edit" size="xs" />
                    </x-aura::button.border>
                </div>
            </div>

            <div class="flex flex-col text-xs">
                <label class="mt-2 font-semibold text-gray-900">Type</label>
                <div class="text-gray-500 select-text" x-text="operation.type"></div>

                <label class="mt-2 font-semibold text-gray-900">Status</label>
                <div class="text-gray-500 select-text" x-text="operation.status"></div>



                <template x-if="operation.type == 'Aura\\Base\\Operations\\Log'">
                    <div class="mt-2">
                        <label class="font-semibold text-gray-900">Message</label>
                        <div class="text-gray-500 select-text" x-text="operation.options.message"></div>
                    </div>
                </template>
            </div>


            <div class="absolute top-[30px] left-[-0.5rem] connect-to">
                <div class="w-4 h-4 bg-white rounded-full border-2 border-gray-500"></div>
            </div>

            <template x-if="operation.resolve_id">
                <div
                    class="absolute top-[30px] right-[-0.5rem] connect-resolve"
                    @mousedown="connectResolve($event, operation)"
                    >
                    <div class="w-4 h-4 bg-white rounded-full border-2 border-primary-400"></div>
                </div>
            </template>
            <template x-if="!operation.resolve_id">
                <div>
                    <div
                    x-data x-ref="this" x-init="tippy($refs.this, { content: 'Drag to operation to reroute', arrow: false, theme: 'aura', placement: 'top', offset: [0, 8], })"
                    class="absolute top-[30px] right-[-0.5rem] connect-resolve"
                    @mousedown="connectResolve($event, operation)"
                    >
                    <div class="w-4 h-4 bg-white rounded-full border-2 border-primary-400"></div>
                </div>
                <div
                    x-data x-ref="this" x-init="tippy($refs.this, { content: 'Click to add operation', arrow: false, theme: 'aura', placement: 'top', offset: [0, 8], })"
                    class="absolute top-[30px] right-[-2rem] connect-resolve group cursor-pointer"
                    @mousedown="addResolve($event, operation)"
                    >

                    <div class="flex justify-center items-center w-4 h-4 font-semibold leading-none text-gray-300 bg-white rounded-full border-2 border-gray-500/30 group-hover:border-primary-400 group-hover:text-primary-500"> <span class="inline-block mt-[-1px]">+</span> </div>
                </div>
                </div>
            </template>

            <template x-if="operation.reject_id">
                <div
                    class="absolute bottom-[30px] right-[-0.5rem] connect-reject"
                    @mousedown="connectReject($event, operation)"
                    >
                    <div class="w-4 h-4 bg-white rounded-full border-2 border-red-400"></div>
                </div>
            </template>
            <template x-if="!operation.reject_id">
                <div>
                    <div
                    x-data x-ref="this" x-init="tippy($refs.this, { content: 'Drag to operation to reroute', arrow: false, theme: 'aura', placement: 'top', offset: [0, 8], })"
                    class="absolute bottom-[30px] right-[-0.5rem] connect-reject"
                    @mousedown="connectReject($event, operation)"
                    >
                    <div class="w-4 h-4 bg-white rounded-full border-2 border-red-400"></div>
                </div>
                <div
                    x-data x-ref="this" x-init="tippy($refs.this, { content: 'Click to add operation', arrow: false, theme: 'aura', placement: 'top', offset: [0, 8], })"
                    class="absolute bottom-[30px] right-[-2rem] connect-reject group cursor-pointer"
                    @mousedown="addReject($event, operation)"
                    >

                    <div class="flex justify-center items-center w-4 h-4 font-semibold leading-none text-gray-300 bg-white rounded-full border-2 border-gray-500/30 group-hover:border-red-400 group-hover:text-red-500"> <span class="inline-block mt-[-1px]">+</span></div>
                </div>
                </div>
            </template>


    </div>
    </template>
    </template>


    </div>
</div>

<livewire:aura::edit-operation />


{{-- <button wire:click="createOperation" class="p-2 text-white bg-blue-500 rounded-full">Create Operation</button> --}}

<script >
    class Vector2 {
        x = 0;
        y = 0;

        constructor(x, y) {
            this.x = x;
            this.y = y;
        }

        clone() {
            return new Vector2(this.x, this.y);
        }

        add(vector) {
            this.x += vector.x;
            this.y += vector.y;
            return this;
        }

        mul(val) {
            if (val instanceof Vector2) {
                this.x *= val.x;
                this.y *= val.y;
            } else {
                this.x *= val;
                this.y *= val;
            }
            return this;
        }

        distanceTo(point) {
            return this.diff(point).length();
        }

        diff(point) {
            return new Vector2(point.x - this.x, point.y - this.y);
        }

        moveNextTo(point, distance = 10) {
            if (this.equals(point)) return point.clone();
            return this.diff(point).normalize().mul(-distance).add(point);
        }

        equals(point) {
            return this.x === point.x && this.y === point.y;
        }

        length() {
            return Math.sqrt(this.x * this.x + this.y * this.y);
        }

        normalize() {
            this.x /= this.length();
            this.y /= this.length();
            return this;
        }

        toString() {
            return `${this.x} ${this.y}`;
        }

        static from(vector) {
            return new Vector2(vector.x, vector.y);
        }
        static fromMany(...vectors) {
            return vectors.map(Vector2.from);
        }
    }
</script>


<script >

    document.addEventListener('livewire:init', function () {
        // Init alpine flow component
        Alpine.data('flow', function () {
            return {

                operations: @entangle('operations'),
                flow: @entangle('flow'),
                selectedOperation: null,
                draggingOperation: false,
                connectingOperation: false,
                connectingFlow: false,
                offsetX: 0,
                offsetY: 0,

                canvasWidth: 1000,
                canvasHeight: 1000,

                furthestX: 0,
                furthestY: 0,

                mouseArrow: '',
                constructedArrows: '',

                init() {
                    this.furthestX = this.getFurthestX();
                    this.furthestY = this.getFurthestY();

                    this.canvasWidth = this.furthestX * 20;
                    this.canvasHeight = this.furthestY * 20;

                    this.constructedArrows = this.constructArrows();

                },
                selectOperation(operation) {
                    @this.selectOperation(operation.id);
                },
                getFurthestX() {
                    let furthestX = 0;
                    // JS foreach this.operations loop
                    this.operations.forEach((operation) => {
                        if (operation.options.x > furthestX) {
                            furthestX = operation.options.x;
                        }
                    });

                    return furthestX + 25;
                },
                getFurthestY() {
                    let furthestY = 0;
                    this.operations.forEach((operation) => {
                        if (operation.options.y > furthestY) {
                            furthestY = operation.options.y;
                        }
                    });
                    return furthestY + 15;
                },
                connectFlowResolve(event) {
                    // the click event should not bubble up to the operation
                    this.connectingFlow = true;
                    this.connectingOperation = false;

                    event.stopPropagation();

                },
                connectResolve(event, operation) {
                    // the click event should not bubble up to the operation
                    operation.operation = 'resolve';

                    this.connectingOperation = operation;

                    event.stopPropagation();

                },

                addResolveFlow(event) {

                    @this.addOperationFlow();

                    event.stopPropagation();

                },

                addResolve(event, operation) {
                    // the click event should not bubble up to the operation
                    operation.operation = 'resolve';

                    this.connectingOperation = operation;

                    @this.addOperation(this.connectingOperation.operation, this.connectingOperation.id);

                    event.stopPropagation();

                },
                addReject(event, operation) {
                    // the click event should not bubble up to the operation
                    operation.operation = 'reject';

                    this.connectingOperation = operation;

                    @this.addOperation(this.connectingOperation.operation, this.connectingOperation.id);

                    event.stopPropagation();

                },
                connectReject(event, operation) {
                    // the click event should not bubble up to the operation
                    operation.operation = 'reject';

                    this.connectingOperation = operation;

                    event.stopPropagation();
                },
                moveOperation(event) {
                    var rect = this.$refs.flows.getBoundingClientRect();

                    if (this.connectingFlow) {
                        this.constructArrowToMouse('flow', event, 'resolve');
                        return;
                    }

                    if (this.connectingOperation) {
                        this.constructArrowToMouse(this.connectingOperation.id, event, this.connectingOperation.operation);
                        return;
                    }

                    if (!this.draggingOperation) {
                        return;
                    }

                    var x = event.clientX - this.offsetX - rect.left;
                    var y = event.clientY - this.offsetY - rect.top;

                    if (this.draggingOperation) {
                        this.selectedOperation.options.x = Math.max(2, Math.round(x / 20));
                        this.selectedOperation.options.y = Math.max(2, Math.round(y / 20));
                    }
                },
                mouseDown(event, operation) {
                    if (this.connectingOperation) {
                        return;
                    }
                    this.draggingOperation = true;
                    this.selectedOperation = operation;

                    // get the bounding client rect of the event target
                    var targetRect = event.target.getBoundingClientRect();
                    // get the difference between the mouse position and the target position
                    this.offsetX = event.clientX - targetRect.left;
                    this.offsetY = event.clientY - targetRect.top;
                },

                constructArrows() {
                    var arrows = '';

                    arrows += this.constructArrowFromFlowToOperation(this.flow.id, this.flow.operation_id, 'resolve');

                    this.operations.forEach(operation => {
                        if (operation.resolve_id) {
                            arrows += this.constructArrow(operation.id, operation.resolve_id, 'resolve',);
                        }
                        if (operation.reject_id) {
                            arrows += this.constructArrow(operation.id, operation.reject_id, 'reject');
                        }
                    });

                    return arrows;

                },

                constructArrowFromFlowToOperation(from, to, operation) {
                    var toOperation = this.operations.find(operation => operation.id == to);

                    if (!toOperation) {
                        return '';
                    }

                    var fromX = 2 * 20 + 180;
                    var fromY = 2 * 20 + 20;

                    var toX = toOperation.options.x * 20 - 20;
                    var toY = toOperation.options.y * 20 + 20;

                    if (operation == 'resolve') {
                        var color = 'green';
                    } else {
                        var color = 'red';
                    }

                    return '<path class="' + operation + '" stroke-linecap="round" d="' + this.createLine(fromX, fromY, toX, toY) + '" />';
                },

                constructArrow(from, to, operation) {

                    var fromOperation = this.operations.find(operation => operation.id == from);
                    var toOperation = this.operations.find(operation => operation.id == to);

                    if (!fromOperation || !toOperation) {
                        return '';
                    }

                    var fromX = fromOperation.options.x * 20 + 180;
                    var fromY = fromOperation.options.y * 20 + 20;

                    if (operation == 'reject') {
                        fromY += 120;
                    }

                    var toX = toOperation.options.x * 20 - 20;
                    var toY = toOperation.options.y * 20 + 20;

                    return '<path class="' + operation + '" stroke-linecap="round" d="' + this.createLine(fromX, fromY, toX, toY) + '" />';
                },


                constructArrowToMouse(from, event, operation) {
                    if (!this.connectingOperation && !this.connectingFlow) {
                        this.mouseArrow = '';
                        return;
                    }

                    var rect = this.$refs.flows.getBoundingClientRect();

                    var x = event.clientX - rect.left;
                    var y = event.clientY - rect.top;

                    if (from == 'flow') {
                        var fromX = 2 * 20 + 180;
                        var fromY = 2 * 20 + 20;
                    } else {
                        var fromOperation = this.operations.find(operation => operation.id == from);
                        var fromX = fromOperation.options.x * 20 + 180;
                        var fromY = fromOperation.options.y * 20 + 20;

                        if (operation == 'reject') {
                            fromY += 120;
                        }
                    }

                    var toX = x;
                    var toY = y;

                    this.mouseArrow = '<path class="' + operation + '" stroke-linecap="round" d="' + this.createLine(fromX, fromY, toX, toY) + '" />';
                },

                cancelMove(event) {
                    if (this.connectingFlow) {

                        let target = event.target;
                        let levels = 0;
                        while (target && !target.hasAttribute('data-operation')) {
                            target = target.parentNode;
                            levels++;
                            if (levels > 5) {
                                break;
                            }
                        }

                        var operationId = null;
                        if (target.getAttribute('data-operation')) {
                            operationId = target.getAttribute('data-operation');
                        }

                        @this.connectFlow(operationId);

                        this.connectingFlow = false;

                        this.connectingOperation = false;
                        this.selectedOperation = null;
                        this.draggingOperation = false;

                        this.constructArrowToMouse();
                        return;
                    }

                    if (this.connectingOperation) {
                        // get the data-operation attribute from the event target
                        // check if event.target has the data-operation attribute, if not, check the parent until it is found
                        let target = event.target;
                        let levels = 0;
                        while (target && !target.hasAttribute('data-operation')) {
                            target = target.parentNode;
                            levels++;
                            if (levels > 5) {
                                break;
                            }
                        }

                        var operationId = null;
                        if (target.getAttribute('data-operation')) {
                            operationId = target.getAttribute('data-operation');
                        }

                        @this.connectOperation(this.connectingOperation.operation, this.connectingOperation.id,  operationId);

                        // refresh the operations from livewire
                        this.connectingOperation = false;
                        this.constructArrowToMouse();
                    }

                    if (this.selectedOperation) {
                        @this.saveOperation(this.selectedOperation);
                    }

                    this.connectingOperation = false;

                    this.selectedOperation = null;
                    this.draggingOperation = false;

                },

                createLine(x, y, toX, toY) {
                    var startOffset = 2;
                    var endOffset = 13;
                    if (y === toY) {
                        return this.generatePath([new Vector2(x + startOffset, y), new Vector2(toX - endOffset, toY)]);
                    }

                    if (x + 3 * 20 < toX) {
                        const centerX = this.findBestPosition(new Vector2(x + 2 * 20, y), new Vector2(toX - 2 * 20, toY), 'x');
                        return this.generatePath([
                            new Vector2(x + startOffset, y),
                            new Vector2(centerX, y),
                            new Vector2(centerX, toY),
                            new Vector2(toX - endOffset, toY)
                        ]);
                    }

                    const offsetBox = 40;
                    const centerY = this.findBestPosition(new Vector2(x + 2 * 20, y), new Vector2(toX - 2 * 20, toY), 'y');
                    return this.generatePath([
                        new Vector2(x + startOffset, y),
                        new Vector2(x + offsetBox, y),
                        new Vector2(x + offsetBox, centerY),
                        new Vector2(toX - offsetBox, centerY),
                        new Vector2(toX - offsetBox, toY),
                        new Vector2(toX - endOffset, toY)
                    ]);
                },

                generatePath(points) {
                    let path = `M ${points[0].x + 8} ${points[0].y}`;

                    if (points.length >= 3) {
                        for (let i = 1; i < points.length - 1; i++) {
                            path += this.generateCorner(points[i - 1], points[i], points[i + 1]);
                        }
                    }

                    const arrowSize = 8;
                    const arrow = `M ${points[points.length - 1].x} ${points[points.length - 1].y} L ${points[points.length - 1].x - arrowSize} ${points[points.length - 1].y - arrowSize} M ${points[points.length - 1].x} ${points[points.length - 1].y} L ${points[points.length - 1].x - arrowSize} ${points[points.length - 1].y + arrowSize}`;

                    return path + ` L ${points[points.length - 1].x} ${points[points.length - 1].y} ${arrow}`;
                },

                generateCorner(start, middle, end) {
                    return ` L ${start.moveNextTo(middle)} Q ${middle} ${end.moveNextTo(middle)}`;
                },

                findBestPosition(from, to, axis) {
                    const possiblePlaces = [];
                    const otherAxis = axis === 'x' ? 'y' : 'x';
                    const {min, max} = this.minMaxPoint(from, to);
                    const outerPoints = this.range(min[otherAxis], max[otherAxis], (axis === 'x' ? 14 : 14) * 20);
                    const innerPoints = this.range(min[axis], max[axis], 20);
                    for (let outer of outerPoints) {
                        for (let inner = 0; inner < innerPoints.length; inner++) {
                            const point = axis === 'x' ? {x: innerPoints[inner], y: outer} : {x: outer, y: innerPoints[inner]};
                            possiblePlaces[inner] = (possiblePlaces[inner] || true) && !this.isPointInPanel(point);
                        }
                    }
                    let pointer = Math.floor(possiblePlaces.length / 2);
                    for (let i = 0; i < possiblePlaces.length; i++) {
                        pointer += i * (i % 2 === 0 ? -1 : 1);
                        if (possiblePlaces[pointer]) return min[axis] + pointer * 20;
                    }
                    return from[axis] + Math.floor((to[axis] - from[axis]) / 2 / 20) * 20;
                },

                range(min, max, step) {
                    const points = [];
                    for (let i = min; i < max; i += step) {
                        points.push(i);
                    }
                    points.push(max);
                    return points;
                },

                isPointInPanel(point, panels, panelWidth, panelHeight) {
                    return true;
                    // return (
                    //     panels.findIndex(
                    //         (panel) =>
                    //         point.x >= (panel.x - 2) * 20 &&
                    //         point.x <= (panel.x - 1 + panelWidth) * 20 &&
                    //         point.y >= (panel.y - 1) * 20 &&
                    //         point.y <= (panel.y - 1 + panelHeight) * 20
                    //     ) !== -1
                    // );
                },

                minMaxPoint(point1, point2) {
                    return {
                        min: { x: Math.min(point1.x, point2.x), y: Math.min(point1.y, point2.y) },
                        max: { x: Math.max(point1.x, point2.x), y: Math.max(point1.y, point2.y) },
                    };
                }


            }
        })
    })



</script>
</div>
