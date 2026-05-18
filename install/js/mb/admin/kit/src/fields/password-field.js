export type PasswordFieldOptions = {
    inputId: string,
    targetId: string,
    textTargetClass: ?string,
    passwordTargetClass: ?string
}
export class PasswordField {

    #inputNode: HTMLElement;
    #targetNode: HTMLElement;
    #textTargetClass: string;
    #passwordTargetClass: string;

    constructor (options: PasswordFieldOptions) {
        this.#inputNode = BX(options.inputId);
        this.#targetNode = BX(options.targetId);

        if (!options.textTargetClass) {
            this.#textTargetClass = 'ui-ctl-icon-opened-eye';
        }

        if (!options.passwordTargetClass) {
            this.#passwordTargetClass = 'ui-ctl-icon-crossed-eye';
        }

        if (!this.#inputNode || !this.#targetNode) {
            return;
        }

        this.switchToPassword();
        this.#init();
    }

    #init() {
        this.#targetNode.addEventListener('click', (e) => {
            e.preventDefault();
            this.switch();
        })
    }

    switch() {
        if (this.#inputNode.type === 'password') {
            this.switchToText();
        } else {
            this.switchToPassword();
        }
    }

    switchToText() {
        this.#inputNode.type = 'text';
        this.#targetNode.classList.remove(this.#passwordTargetClass);
        this.#targetNode.classList.add(this.#textTargetClass);
    }

    switchToPassword() {
        this.#inputNode.type = 'password';
        this.#targetNode.classList.remove(this.#textTargetClass);
        this.#targetNode.classList.add(this.#passwordTargetClass);
    }
}
