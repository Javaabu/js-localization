import { useTranslation } from "@javaabu/i18n/react"

export function Cart({ count }: { count: number }) {
  const { __, trans_choice } = useTranslation()

  return (
    <section>
      <h2>{__("Add to cart")}</h2>
      <p>{trans_choice("messages.apples", count)}</p>
      <p>{__("auth.failed")}</p>
      <p>{__(`Only in English`)}</p>
      <button>{__("Save")}</button>
    </section>
  )
}
