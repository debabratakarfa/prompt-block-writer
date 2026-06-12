import { useState } from "@wordpress/element";
import { Button, Modal, TextareaControl } from "@wordpress/components";
import { useSelect, useDispatch } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { rawHandler } from "@wordpress/blocks";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { marked } from "marked";
import { registerPlugin } from "@wordpress/plugins";
import { __ } from "@wordpress/i18n";
import { store as noticesStore } from "@wordpress/notices";
import apiFetch from "@wordpress/api-fetch";

const ABILITY = "blocksmith-prompts/generate";
const PROVIDER_NOTICE_ID = "blocksmith-prompts-provider-error";

function isAbilityClientFallbackError(error) {
  if (!error || typeof error !== "object") {
    return false;
  }
  const message =
    "message" in error && typeof error.message === "string" ? error.message : "";
  const code = "code" in error && typeof error.code === "string" ? error.code : "";
  return (
    code === "ability_not_found" ||
    message.includes("Ability not found") ||
    message.includes("missing callback") ||
    code === "ability_invalid_input"
  );
}

async function callAbility(input) {
  try {
    const abilities = await (async () => {
      try {
        const { ready } = await import("@wordpress/core-abilities");
        await ready;
        return await import("@wordpress/abilities");
      } catch {
        return null;
      }
    })();

    if (typeof abilities?.executeAbility === "function") {
      return await abilities.executeAbility(ABILITY, input);
    }
  } catch (error) {
    if (!isAbilityClientFallbackError(error)) {
      throw error;
    }
  }

  return apiFetch({
    path: `/wp-abilities/v1/abilities/${ABILITY}/run`,
    method: "POST",
    data: { input },
  });
}

function markdownToBlocks(markdown) {
  const html = marked.parse(markdown, { async: false });
  return rawHandler({ HTML: html });
}

function ensureProviderAvailable(createErrorNotice) {
  const { hasProvider = false, connectorsUrl = "" } = window.aiProviderData ?? {};

  if (hasProvider) {
    return true;
  }

  createErrorNotice(
    __("This feature requires an AI Connector to function properly.", "blocksmith-prompts"),
    {
      id: PROVIDER_NOTICE_ID,
      isDismissible: true,
      actions: connectorsUrl
        ? [
            {
              label: __("Manage Connectors", "blocksmith-prompts"),
              url: connectorsUrl,
            },
          ]
        : [],
    },
  );

  return false;
}

function GenerateContentModal({
  isOpen,
  onClose,
  postContent,
  onInsert,
}) {
  const { createErrorNotice, removeNotice } = useDispatch(noticesStore);
  const [prompt, setPrompt] = useState("");
  const [generated, setGenerated] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleGenerate = async () => {
    const trimmedPrompt = prompt.trim();

    if (!trimmedPrompt) {
      setError(__("Please enter a prompt.", "blocksmith-prompts"));
      return;
    }

    if (!ensureProviderAvailable(createErrorNotice)) {
      return;
    }

    setLoading(true);
    setError("");
    removeNotice(PROVIDER_NOTICE_ID);

    try {
      const output = await callAbility({
        prompt: trimmedPrompt,
        context: postContent,
      });
      setGenerated(typeof output === "string" ? output : "");
    } catch (err) {
      setError(err?.message ?? __("Generation failed.", "blocksmith-prompts"));
    } finally {
      setLoading(false);
    }
  };

  const handleInsert = () => {
    const trimmed = generated.trim();

    if (!trimmed) {
      return;
    }

    onInsert(trimmed);
    onClose();
  };

  const handleClose = () => {
    setPrompt("");
    setGenerated("");
    setError("");
    onClose();
  };

  if (!isOpen) {
    return null;
  }

  const generateLabel = generated
    ? __("Regenerate", "blocksmith-prompts")
    : __("Generate", "blocksmith-prompts");

  return (
    <Modal
      title={__("AI Content Generator", "blocksmith-prompts")}
      onRequestClose={handleClose}
      className="blocksmith-prompts-modal"
      size="medium"
    >
      <div className="blocksmith-prompts-modal__content">
        <TextareaControl
          label={__("Prompt", "blocksmith-prompts")}
          value={prompt}
          onChange={setPrompt}
          rows={4}
          help={__(
            "Describe what you want written. Current post content is used as context when available.",
            "blocksmith-prompts",
          )}
        />

        {generated && (
          <TextareaControl
            label={__("Generated content", "blocksmith-prompts")}
            value={generated}
            onChange={setGenerated}
            rows={10}
          />
        )}

        {error && (
          <p className="blocksmith-prompts-modal__error" style={{ color: "#cc1818" }}>
            {error}
          </p>
        )}

        <div className="blocksmith-prompts-modal__actions" style={{ display: "flex", gap: "8px", marginTop: "16px" }}>
          {generated ? (
            <Button
              variant="primary"
              onClick={handleInsert}
              disabled={!generated.trim()}
            >
              {__("Insert into editor", "blocksmith-prompts")}
            </Button>
          ) : null}

          <Button
            variant={generated ? "secondary" : "primary"}
            onClick={handleGenerate}
            disabled={loading || !prompt.trim()}
            isBusy={loading}
          >
            {loading
              ? __("Generating…", "blocksmith-prompts")
              : generateLabel}
          </Button>

          <Button variant="tertiary" onClick={handleClose}>
            {__("Cancel", "blocksmith-prompts")}
          </Button>
        </div>
      </div>
    </Modal>
  );
}

function ContentGeneratorPanel() {
  const { createErrorNotice } = useDispatch(noticesStore);
  const { insertBlocks } = useDispatch(blockEditorStore);
  const postContent = useSelect((select) =>
    select(editorStore).getEditedPostContent(),
  );
  const [modalOpen, setModalOpen] = useState(false);

  const handleOpenModal = () => {
    if (!ensureProviderAvailable(createErrorNotice)) {
      return;
    }
    setModalOpen(true);
  };

  const handleInsert = (text) => {
    const blocks = markdownToBlocks(text);

    if (blocks.length === 0) {
      return;
    }

    insertBlocks(blocks);
  };

  return (
    <PluginDocumentSettingPanel
      name="blocksmith-prompts-panel"
      title={__("AI Content Generator", "blocksmith-prompts")}
      className="blocksmith-prompts-settings-panel"
    >
      <Button variant="primary" onClick={handleOpenModal}>
        {__("Generate from context", "blocksmith-prompts")}
      </Button>

      <GenerateContentModal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        postContent={postContent}
        onInsert={handleInsert}
      />
    </PluginDocumentSettingPanel>
  );
}

registerPlugin("blocksmith-prompts", {
  render: () => <ContentGeneratorPanel />,
});
