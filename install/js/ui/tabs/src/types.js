
export type TabsOptionsType = {
	id: ?string,
	items?: Array<TabOptionsType>,
	hashNavigation?: boolean,
	scrollable?: boolean,
}

export type TabHeadOptionsType = {
	title: string,
	description: ?string,
	className: ?string,
	icon: ?string,
	count: ?number,
}

export type TabOptionsType = {
	id?: string,
	sort?: number,
	active?: boolean,
	restricted?: boolean,
	bannerCode?: string,
	helpDeskCode?: string,

	head: TabHeadOptionsType | HTMLElement,
	body: string | Function | Promise | HTMLElement,
}
