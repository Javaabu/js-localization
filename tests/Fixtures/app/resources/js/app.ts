import { __, configure, ready } from "@javaabu/i18n"
import { loader } from "@javaabu/js-localization/translations"

configure({ loader })

ready().then(() => {
  document.body.dataset.save = __("Save")
})
